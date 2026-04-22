<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * Validate coupon and return the discount amount.
     * Throws ValidationException if coupon is invalid.
     */
    public function apply(string $code, float $subtotal): array
    {
        $coupon = Coupon::where('code', strtoupper($code))
            ->lockForUpdate() // prevent race conditions on max_uses
            ->first();

        if (!$coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon code is invalid.'],
            ]);
        }

        if (!$coupon->isValid($subtotal)) {
            $this->throwInvalidCouponError($coupon, $subtotal);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'coupon'   => $coupon,
            'discount' => $discount,
        ];
    }

    /**
     * Increment usage count after order is placed.
     */
    public function incrementUsage(Coupon $coupon): void
    {
        $coupon->increment('used_count');
    }

    /**
     * Throw a specific error message based on why coupon is invalid.
     */
    private function throwInvalidCouponError(Coupon $coupon, float $subtotal): void
    {
        if (!$coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon is no longer active.'],
            ]);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has reached its usage limit.'],
            ]);
        }

        if ($subtotal < $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => [
                    "Minimum order amount of ৳{$coupon->min_order_amount} required for this coupon."
                ],
            ]);
        }

        throw ValidationException::withMessages([
            'coupon_code' => ['This coupon is invalid.'],
        ]);
    }
}