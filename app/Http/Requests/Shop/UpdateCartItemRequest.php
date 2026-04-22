<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qty' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}