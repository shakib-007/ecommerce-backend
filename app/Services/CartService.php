<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Get or create the user's cart.
     * We use firstOrCreate so the cart is
     * automatically created on first use.
     */
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $user->id]
        );
    }

    /**
     * Get cart with all necessary relations loaded.
     */
    public function getCartWithItems(User $user): Cart
    {
        $cart = $this->getOrCreateCart($user);

        return $cart->load([
            'items.variant.attributeValues.group',
            'items.variant.product.images',
        ]);
    }

    /**
     * Add a variant to the cart.
     * If variant already exists → increase quantity.
     * If variant is new → create new cart item.
     */
    public function addItem(User $user, string $variantId, int $qty): Cart
    {
        $variant = ProductVariant::findOrFail($variantId);

        // Check variant is active
        if (!$variant->is_active) {
            throw ValidationException::withMessages([
                'variant_id' => ['This product variant is not available.'],
            ]);
        }

        // Check stock
        if ($variant->stock_qty < $qty) {
            throw ValidationException::withMessages([
                'qty' => ["Only {$variant->stock_qty} items available in stock."],
            ]);
        }

        $cart = $this->getOrCreateCart($user);

        // Check if this variant is already in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($existingItem) {
            $newQty = $existingItem->qty + $qty;

            // Check stock for combined quantity
            if ($variant->stock_qty < $newQty) {
                throw ValidationException::withMessages([
                    'qty' => ["Only {$variant->stock_qty} items available. You already have {$existingItem->qty} in cart."],
                ]);
            }

            $existingItem->update(['qty' => $newQty]);
        } else {
            CartItem::create([
                'cart_id'    => $cart->id,
                'variant_id' => $variantId,
                'qty'        => $qty,
            ]);
        }

        return $this->getCartWithItems($user);
    }

    /**
     * Update quantity of a specific cart item.
     */
    public function updateItem(User $user, string $cartItemId, int $qty): Cart
    {
        $cart = $this->getOrCreateCart($user);

        $item = CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id) // ensure item belongs to this user's cart
            ->firstOrFail();

        // Check stock for new quantity
        if ($item->variant->stock_qty < $qty) {
            throw ValidationException::withMessages([
                'qty' => ["Only {$item->variant->stock_qty} items available in stock."],
            ]);
        }

        $item->update(['qty' => $qty]);

        return $this->getCartWithItems($user);
    }

    /**
     * Remove a specific item from cart.
     */
    public function removeItem(User $user, string $cartItemId): Cart
    {
        $cart = $this->getOrCreateCart($user);

        CartItem::where('id', $cartItemId)
            ->where('cart_id', $cart->id)
            ->firstOrFail()
            ->delete();

        return $this->getCartWithItems($user);
    }

    /**
     * Empty the entire cart.
     * Called after successful order placement.
     */
    public function clearCart(User $user): void
    {
        $cart = $this->getOrCreateCart($user);
        $cart->items()->delete();
    }

    /**
     * Get total item count — used for cart badge in navbar.
     */
    public function getItemCount(User $user): int
    {
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) return 0;

        return CartItem::where('cart_id', $cart->id)->sum('qty');
    }
}