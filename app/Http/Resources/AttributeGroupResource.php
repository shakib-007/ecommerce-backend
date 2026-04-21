<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'type'   => $this->type,
            'values' => $this->whenLoaded('values', fn() =>
                $this->values->map(fn($val) => [
                    'id'    => $val->id,
                    'value' => $val->value,
                    'meta'  => $val->meta,
                ])
            ),
        ];
    }
}