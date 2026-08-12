<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Query strings send "true"/"false" as strings; Laravel's boolean rule
     * only accepts true/false/0/1/"0"/"1" unless we normalize first.
     */
    protected function prepareForValidation(): void
    {
        foreach (['featured', 'in_stock'] as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'category'    => ['nullable', 'string', 'exists:categories,slug'],
            'brand'       => ['nullable', 'string', 'exists:brands,slug'],
            'search'      => ['nullable', 'string', 'max:100'],
            'min_price'   => ['nullable', 'numeric', 'min:0'],
            'max_price'   => ['nullable', 'numeric', 'min:0'],
            'featured'    => ['sometimes', 'boolean'],
            'in_stock'    => ['sometimes', 'boolean'],
            'sort'        => ['nullable', 'in:price_asc,price_desc,newest,popular'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'attributes'  => ['nullable', 'array'],
            'attributes.*'=> ['string'],
        ];
    }
}