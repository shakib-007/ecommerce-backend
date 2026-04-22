<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => [
                'required',
                'uuid',
                'exists:addresses,id,user_id,' . $this->user()->id,
            ],
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => [
                'required',
                'in:sslcommerz,cod',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.exists' => 'Selected address not found or does not belong to you.',
        ];
    }
}