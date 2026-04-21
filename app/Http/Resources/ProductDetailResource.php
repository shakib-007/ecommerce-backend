<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'base_price'  => (float) $this->base_price,
            'is_featured' => $this->is_featured,

            // All images for the gallery
            'images'      => $this->whenLoaded('images', fn() =>
                $this->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => $img->url,
                    'is_primary' => $img->is_primary,
                    'variant_id' => $img->variant_id,
                ])
            ),

            // All variants with their attributes
            'variants'    => ProductVariantResource::collection(
                $this->whenLoaded('variants')
            ),

            // Unique attribute groups across all variants
            // Frontend uses this to render the variant selector UI
            'attribute_groups' => $this->whenLoaded(
                'variants',
                fn() => $this->buildAttributeGroups()
            ),

            'category'    => new CategoryResource(
                $this->whenLoaded('category')
            ),
            'brand'       => new BrandResource(
                $this->whenLoaded('brand')
            ),

            'rating_avg'   => round($this->reviews->avg('rating') ?? 0, 1),
            'rating_count' => $this->reviews->count(),
            'reviews'      => $this->whenLoaded('reviews', fn() =>
                $this->reviews->where('is_approved', true)
                    ->take(5)
                    ->map(fn($r) => [
                        'id'         => $r->id,
                        'rating'     => $r->rating,
                        'title'      => $r->title,
                        'body'       => $r->body,
                        'user_name'  => $r->user->name,
                        'created_at' => $r->created_at->toISOString(),
                    ])
            ),
        ];
    }

    /**
     * Build unique attribute groups for the variant selector.
     * Example output:
     * [
     *   { name: "Color", type: "color", values: [{id, value, meta}] },
     *   { name: "Storage", type: "select", values: [{id, value, meta}] },
     * ]
     */
    private function buildAttributeGroups(): array
    {
        $groups = [];

        foreach ($this->variants as $variant) {
            if (!$variant->relationLoaded('attributeValues')) continue;

            foreach ($variant->attributeValues as $av) {
                $groupName = $av->group->name;

                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = [
                        'id'     => $av->group->id,
                        'name'   => $groupName,
                        'type'   => $av->group->type,
                        'values' => [],
                    ];
                }

                // Avoid duplicate values in the group
                $exists = collect($groups[$groupName]['values'])
                    ->contains('id', $av->id);

                if (!$exists) {
                    $groups[$groupName]['values'][] = [
                        'id'    => $av->id,
                        'value' => $av->value,
                        'meta'  => $av->meta,
                    ];
                }
            }
        }

        return array_values($groups);
    }
}