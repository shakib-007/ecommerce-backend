<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'    => ['nullable', 'string', 'exists:categories,slug'],
            'brand'       => ['nullable', 'string', 'exists:brands,slug'],
            'search'      => ['nullable', 'string', 'max:100'],
            'min_price'   => ['nullable', 'numeric', 'min:0'],
            'max_price'   => ['nullable', 'numeric', 'min:0'],
            'featured'    => ['nullable', 'boolean'],
            'in_stock'    => ['nullable', 'boolean'],
            'sort'        => ['nullable', 'in:price_asc,price_desc,newest,popular'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'attributes'  => ['nullable', 'array'],
            'attributes.*'=> ['string'],
        ];
    }
}