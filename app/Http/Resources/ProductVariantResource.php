<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'sku'           => $this->sku,
            'price'         => (float) $this->price,
            'compare_price' => $this->compare_price
                                ? (float) $this->compare_price
                                : null,
            'stock_qty'     => $this->stock_qty,
            'in_stock'      => $this->stock_qty > 0,
            'is_active'     => $this->is_active,

            // The attributes that define this variant (Color: Black, Storage: 128GB)
            'attributes'    => $this->whenLoaded(
                'attributeValues',
                fn() => $this->attributeValues->map(fn($av) => [
                    'group_id'   => $av->group->id,
                    'group_name' => $av->group->name,
                    'group_type' => $av->group->type,
                    'value_id'   => $av->id,
                    'value'      => $av->value,
                    'meta'       => $av->meta,
                ])
            ),
        ];
    }
}