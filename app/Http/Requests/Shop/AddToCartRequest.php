<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => [
                'required',
                'uuid',
                'exists:product_variants,id',
            ],
            'qty' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.exists' => 'This product variant does not exist.',
            'qty.min'           => 'Quantity must be at least 1.',
            'qty.max'           => 'Maximum 100 items per product.',
        ];
    }
}