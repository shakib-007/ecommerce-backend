<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'status'         => $this->status,
            'subtotal'       => (float) $this->subtotal,
            'discount_total' => (float) $this->discount_total,
            'shipping_fee'   => (float) $this->shipping_fee,
            'total'          => (float) $this->total,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at->toISOString(),

            // Shipping address snapshot
            'address'        => $this->whenLoaded('address', fn() => [
                'label'       => $this->address->label,
                'line1'       => $this->address->line1,
                'line2'       => $this->address->line2,
                'city'        => $this->address->city,
                'state'       => $this->address->state,
                'postal_code' => $this->address->postal_code,
                'country'     => $this->address->country,
            ]),

            // Order items using the frozen snapshot
            'items'          => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'               => $item->id,
                    'qty'              => $item->qty,
                    'unit_price'       => (float) $item->unit_price,
                    'line_total'       => (float) $item->line_total,
                    // Use snapshot — not live variant data
                    // because price/name may have changed since order
                    'product_name'     => $item->variant_snapshot['product_name'],
                    'sku'              => $item->variant_snapshot['sku'],
                    'attributes'       => $item->variant_snapshot['attrs'] ?? [],
                    // Image from variant still fine to use live
                    'image'            => $item->variant->product->images
                                            ->where('is_primary', true)
                                            ->first()?->url,
                ])
            ),

            // Payment info
            'payment'        => $this->whenLoaded('latestPayment', fn() =>
                $this->latestPayment ? [
                    'gateway'    => $this->latestPayment->gateway,
                    'status'     => $this->latestPayment->status,
                    'amount'     => (float) $this->latestPayment->amount,
                    'paid_at'    => $this->latestPayment->paid_at?->toISOString(),
                ] : null
            ),

            // Coupons applied
            'coupons'        => $this->whenLoaded('coupons', fn() =>
                $this->coupons->map(fn($coupon) => [
                    'code'             => $coupon->code,
                    'discount_applied' => (float) $coupon->pivot->discount_applied,
                ])
            ),
        ];
    }
}