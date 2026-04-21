<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get the cheapest active variant price for display
        $cheapestVariant = $this->variants
            ->where('is_active', true)
            ->sortBy('price')
            ->first();

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'base_price'  => (float) $this->base_price,

            // Show price range on listing cards
            'price_from'  => $cheapestVariant
                                ? (float) $cheapestVariant->price
                                : (float) $this->base_price,

            'is_featured' => $this->is_featured,
            'in_stock'    => $this->variants->sum('stock_qty') > 0,

            // Primary image only for listing cards
            'image'       => $this->whenLoaded('images', fn() =>
                $this->images->where('is_primary', true)->first()?->url
                ?? $this->images->first()?->url
            ),

            'category'    => new CategoryResource(
                $this->whenLoaded('category')
            ),
            'brand'       => new BrandResource(
                $this->whenLoaded('brand')
            ),

            // Average rating
            'rating_avg'  => $this->when(
                isset($this->rating_avg),
                fn() => round($this->rating_avg, 1)
            ),
            'rating_count' => $this->when(
                isset($this->rating_count),
                $this->rating_count
            ),
        ];
    }
}