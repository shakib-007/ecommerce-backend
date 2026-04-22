<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'total_items'    => $this->whenLoaded('items', fn() =>
                $this->items->sum('qty')
            ),
            'payment_status' => $this->whenLoaded('latestPayment', fn() =>
                $this->latestPayment?->status ?? 'pending'
            ),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}