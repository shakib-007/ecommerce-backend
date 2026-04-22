<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'total_items' => $this->total_items,
            'subtotal'    => round($this->subtotal, 2),
            'items'       => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'         => $item->id,
                    'qty'        => $item->qty,
                    'line_total' => round($item->line_total, 2),
                    'variant'    => [
                        'id'            => $item->variant->id,
                        'sku'           => $item->variant->sku,
                        'price'         => (float) $item->variant->price,
                        'compare_price' => $item->variant->compare_price
                                            ? (float) $item->variant->compare_price
                                            : null,
                        'stock_qty'     => $item->variant->stock_qty,
                        'in_stock'      => $item->variant->stock_qty > 0,
                        'attributes'    => $item->variant->attributeValues
                            ->map(fn($av) => [
                                'group' => $av->group->name,
                                'value' => $av->value,
                                'meta'  => $av->meta,
                            ]),
                        'product' => [
                            'id'   => $item->variant->product->id,
                            'name' => $item->variant->product->name,
                            'slug' => $item->variant->product->slug,
                            'image' => $item->variant->product->images
                                ->where('is_primary', true)
                                ->first()?->url
                                ?? $item->variant->product->images->first()?->url,
                        ],
                    ],
                ])
            ),
        ];
    }
}