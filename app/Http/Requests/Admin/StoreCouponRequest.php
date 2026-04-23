<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'             => ['required', 'string', 'unique:coupons,code'],
            'type'             => ['required', 'in:flat,percentage'],
            'value'            => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses'         => ['nullable', 'integer', 'min:1'],
            'expires_at'       => ['nullable', 'date', 'after:today'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }
}