<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class GuestPlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:sslcommerz,cod'],
            'coupon_code'    => ['nullable', 'string', 'exists:coupons,code'],
            'notes'          => ['nullable', 'string', 'max:500'],

            'guest.name'  => ['required', 'string', 'max:120'],
            'guest.email' => ['required', 'email', 'max:255'],
            'guest.phone' => ['required', 'string', 'max:30'],

            'shipping_address.line1'       => ['required', 'string', 'max:255'],
            'shipping_address.line2'       => ['nullable', 'string', 'max:255'],
            'shipping_address.city'        => ['required', 'string', 'max:120'],
            'shipping_address.state'       => ['nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address.country'     => ['required', 'string', 'max:100'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
            'items.*.qty'        => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
