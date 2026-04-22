<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\CouponService;

class UpdateCouponUsage
{
    public function __construct(private CouponService $couponService) {}

    public function handle(OrderPlaced $event): void
    {
        // Increment used_count for each coupon applied to this order
        foreach ($event->order->coupons as $coupon) {
            $this->couponService->incrementUsage($coupon);
        }
    }
}