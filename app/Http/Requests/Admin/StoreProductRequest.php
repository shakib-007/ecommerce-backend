<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'brand_id'    => ['nullable', 'uuid', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'base_price'  => ['required', 'numeric', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],

            // Variants array
            'variants'                       => ['required', 'array', 'min:1'],
            'variants.*.sku'                 => ['required', 'string', 'distinct'],
            'variants.*.price'               => ['required', 'numeric', 'min:0'],
            'variants.*.compare_price'       => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_qty'           => ['required', 'integer', 'min:0'],
            'variants.*.is_active'           => ['nullable', 'boolean'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['uuid', 'exists:attribute_values,id'],

            // Images
            'images'          => ['nullable', 'array'],
            'images.*'        => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'primary_image'   => ['nullable', 'integer'], // index of primary image
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required'       => 'At least one variant is required.',
            'variants.*.sku.distinct' => 'All variant SKUs must be unique.',
            'variants.*.sku.required' => 'Each variant must have a SKU.',
        ];
    }
}