<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku'                  => ['required', 'string', 'unique:product_variants,sku'],
            'price'                => ['required', 'numeric', 'min:0'],
            'compare_price'        => ['nullable', 'numeric', 'min:0'],
            'stock_qty'            => ['required', 'integer', 'min:0'],
            'is_active'            => ['nullable', 'boolean'],
            'attribute_value_ids'  => ['nullable', 'array'],
            'attribute_value_ids.*'=> ['uuid', 'exists:attribute_values,id'],
        ];
    }
}