<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CouponService $couponService,
        private CartService   $cartService,
    ) {}

    /**
     * Place an order from the user's cart.
     * The entire process runs inside a database transaction.
     * If anything fails, everything rolls back automatically.
     */
    public function placeOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {

            // ── 1. Load cart items ────────────────────────────────
            $cart = Cart::where('user_id', $user->id)
                ->with('items.variant')
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            // ── 2. Lock & validate stock ──────────────────────────
            // lockForUpdate() prevents other transactions from
            // modifying these rows until this transaction completes.
            // This is how we prevent overselling.
            $variantIds = $cart->items->pluck('variant_id');

            $variants = ProductVariant::whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $variant = $variants->get($item->variant_id);

                if (!$variant || !$variant->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => [
                            "'{$variant?->sku}' is no longer available."
                        ],
                    ]);
                }

                if ($variant->stock_qty < $item->qty) {
                    throw ValidationException::withMessages([
                        'cart' => [
                            "Only {$variant->stock_qty} units of '{$variant->sku}' available."
                        ],
                    ]);
                }
            }

            // ── 3. Calculate subtotal ─────────────────────────────
            $subtotal = $cart->items->sum(
                fn($item) => $variants->get($item->variant_id)->price * $item->qty
            );

            // ── 4. Apply coupon if provided ───────────────────────
            $discountTotal = 0;
            $appliedCoupon = null;

            if (!empty($data['coupon_code'])) {
                $couponResult  = $this->couponService->apply(
                    $data['coupon_code'],
                    $subtotal
                );
                $discountTotal = $couponResult['discount'];
                $appliedCoupon = $couponResult['coupon'];
            }

            // ── 5. Calculate shipping ─────────────────────────────
            $freeThreshold = (float) SiteSetting::get('free_shipping_threshold', 2000);
            $shippingFee   = ($subtotal - $discountTotal) >= $freeThreshold
                ? 0
                : (float) SiteSetting::get('default_shipping_fee', 120);

            $total = $subtotal - $discountTotal + $shippingFee;

            // ── 6. Create order ───────────────────────────────────
            $order = Order::create([
                'user_id'        => $user->id,
                'address_id'     => $data['address_id'],
                'order_number'   => $this->generateOrderNumber(),
                'status'         => 'pending',
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_fee'   => $shippingFee,
                'total'          => $total,
                'notes'          => $data['notes'] ?? null,
            ]);

            // ── 7. Create order items with frozen snapshots ───────
            foreach ($cart->items as $item) {
                $variant = $variants->get($item->variant_id);

                // Load product name for snapshot
                $variant->load('product', 'attributeValues.group');

                $order->items()->create([
                    'variant_id'       => $variant->id,
                    'qty'              => $item->qty,
                    'unit_price'       => $variant->price,
                    'line_total'       => $variant->price * $item->qty,
                    // Snapshot freezes name/attrs at purchase time
                    'variant_snapshot' => [
                        'product_name' => $variant->product->name,
                        'sku'          => $variant->sku,
                        'attrs'        => $variant->attributeValues
                            ->mapWithKeys(fn($av) => [
                                $av->group->name => $av->value
                            ])
                            ->toArray(),
                    ],
                ]);

                // ── 8. Decrement stock atomically ─────────────────
                // Using decrement() with a where clause to prevent
                // stock going below zero as a safety net
                ProductVariant::where('id', $variant->id)
                    ->where('stock_qty', '>=', $item->qty)
                    ->decrement('stock_qty', $item->qty);
            }

            // ── 9. Attach coupon to order ─────────────────────────
            if ($appliedCoupon) {
                $order->coupons()->attach($appliedCoupon->id, [
                    'discount_applied' => $discountTotal,
                ]);
            }

           // ── 10. Create payment record ─────────────────────────────
            $paymentStatus = $data['payment_method'] === 'cod'
                ? 'pending'  // paid on delivery
                : 'pending'; // waiting for gateway

            // COD orders go straight to confirmed
            // SSLCommerz orders stay pending until IPN webhook
            $orderStatus = $data['payment_method'] === 'cod'
                ? 'confirmed'
                : 'pending';

            $order->update(['status' => $orderStatus]);

            Payment::create([
                'order_id' => $order->id,
                'gateway'  => $data['payment_method'],
                'amount'   => $total,
                'status'   => $paymentStatus,
                'meta'     => ['method' => $data['payment_method']],
            ]);

            // ── 11. Clear the cart ────────────────────────────────
            $this->cartService->clearCart($user);

            $order->load([
                'items.variant.product.images',
                'address',
                'latestPayment',
                'coupons',
            ]);

            // Fire after commit so email failures cannot roll back the order
            DB::afterCommit(function () use ($order) {
                OrderPlaced::dispatch($order);
            });

            return $order;
        });
    }

    /**
     * Place an order for a guest (no account / not signed in).
     * Items come from the request payload instead of a server cart.
     */
    public function placeGuestOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = collect($data['items']);

            $variantIds = $items->pluck('variant_id');

            $variants = ProductVariant::whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);

                if (!$variant || !$variant->is_active) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "'{$variant?->sku}' is no longer available."
                        ],
                    ]);
                }

                if ($variant->stock_qty < $item['qty']) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Only {$variant->stock_qty} units of '{$variant->sku}' available."
                        ],
                    ]);
                }
            }

            $subtotal = $items->sum(
                fn($item) => $variants->get($item['variant_id'])->price * $item['qty']
            );

            $discountTotal = 0;
            $appliedCoupon = null;

            if (!empty($data['coupon_code'])) {
                $couponResult  = $this->couponService->apply(
                    $data['coupon_code'],
                    $subtotal
                );
                $discountTotal = $couponResult['discount'];
                $appliedCoupon = $couponResult['coupon'];
            }

            $freeThreshold = (float) SiteSetting::get('free_shipping_threshold', 2000);
            $shippingFee   = ($subtotal - $discountTotal) >= $freeThreshold
                ? 0
                : (float) SiteSetting::get('default_shipping_fee', 120);

            $total = $subtotal - $discountTotal + $shippingFee;

            $shipping = $data['shipping_address'];

            $address = Address::create([
                'user_id'     => null,
                'label'       => 'Delivery',
                'line1'       => $shipping['line1'],
                'line2'       => $shipping['line2'] ?? null,
                'city'        => $shipping['city'],
                'state'       => $shipping['state'] ?? null,
                'postal_code' => $shipping['postal_code'] ?? null,
                'country'     => $shipping['country'] ?? 'Bangladesh',
                'is_default'  => false,
            ]);

            $guest = $data['guest'];

            $order = Order::create([
                'user_id'        => null,
                'guest_name'     => $guest['name'],
                'guest_email'    => $guest['email'],
                'guest_phone'    => $guest['phone'],
                'address_id'     => $address->id,
                'order_number'   => $this->generateOrderNumber(),
                'status'         => 'pending',
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_fee'   => $shippingFee,
                'total'          => $total,
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                $variant->load('product', 'attributeValues.group');

                $order->items()->create([
                    'variant_id'       => $variant->id,
                    'qty'              => $item['qty'],
                    'unit_price'       => $variant->price,
                    'line_total'       => $variant->price * $item['qty'],
                    'variant_snapshot' => [
                        'product_name' => $variant->product->name,
                        'sku'          => $variant->sku,
                        'attrs'        => $variant->attributeValues
                            ->mapWithKeys(fn($av) => [
                                $av->group->name => $av->value
                            ])
                            ->toArray(),
                    ],
                ]);

                ProductVariant::where('id', $variant->id)
                    ->where('stock_qty', '>=', $item['qty'])
                    ->decrement('stock_qty', $item['qty']);
            }

            if ($appliedCoupon) {
                $order->coupons()->attach($appliedCoupon->id, [
                    'discount_applied' => $discountTotal,
                ]);
            }

            $orderStatus = $data['payment_method'] === 'cod'
                ? 'confirmed'
                : 'pending';

            $order->update(['status' => $orderStatus]);

            Payment::create([
                'order_id' => $order->id,
                'gateway'  => $data['payment_method'],
                'amount'   => $total,
                'status'   => 'pending',
                'meta'     => ['method' => $data['payment_method'], 'guest' => true],
            ]);

            $order->load([
                'items.variant.product.images',
                'address',
                'latestPayment',
                'coupons',
            ]);

            DB::afterCommit(function () use ($order) {
                OrderPlaced::dispatch($order);
            });

            return $order;
        });
    }

    /**
     * Generate a unique, human-readable order number.
     * Format: ORD-20240315-4892
     */
    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Get orders for the authenticated user.
     */
    public function getUserOrders(User $user)
    {
        return Order::where('user_id', $user->id)
            ->with(['items', 'latestPayment'])
            ->latest()
            ->paginate(10);
    }

    /**
     * Get a single order — ensures it belongs to this user.
     */
    public function getUserOrder(User $user, string $orderId): Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with([
                'items.variant.product.images',
                'address',
                'latestPayment',
                'coupons',
            ])
            ->firstOrFail();
    }
}