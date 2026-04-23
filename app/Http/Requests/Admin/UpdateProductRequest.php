<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'brand_id'    => ['nullable', 'uuid', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'base_price'  => ['sometimes', 'numeric', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}