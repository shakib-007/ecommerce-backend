<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image_url'   => $this->image_url,
            'sort_order'  => $this->sort_order,

            // Only include children if they were eager-loaded
            'children'    => CategoryResource::collection(
                $this->whenLoaded('children')
            ),

            // Only include parent if eager-loaded
            'parent'      => new CategoryResource(
                $this->whenLoaded('parent')
            ),

            'products_count' => $this->when(
                isset($this->products_count),
                $this->products_count
            ),
        ];
    }
}