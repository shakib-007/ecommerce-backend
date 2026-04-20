<?php
namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code'             => 'WELCOME10',
                'type'             => 'percentage',
                'value'            => 10,
                'min_order_amount' => 500,
                'max_uses'         => 100,
                'expires_at'       => now()->addMonths(6),
                'is_active'        => true,
            ],
            [
                'code'             => 'FLAT500',
                'type'             => 'flat',
                'value'            => 500,
                'min_order_amount' => 3000,
                'max_uses'         => 50,
                'expires_at'       => now()->addMonths(3),
                'is_active'        => true,
            ],
            [
                'code'             => 'SUMMER20',
                'type'             => 'percentage',
                'value'            => 20,
                'min_order_amount' => 1000,
                'max_uses'         => null, // unlimited
                'expires_at'       => now()->addMonth(),
                'is_active'        => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }

        $this->command->info('✅ Coupons seeded');
    }
}