<?php
namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name',       'value' => 'MyShop',               'group' => 'general'],
            ['key' => 'site_tagline',    'value' => 'Best deals every day',  'group' => 'general'],
            ['key' => 'site_email',      'value' => 'support@myshop.com',    'group' => 'general'],
            ['key' => 'site_phone',      'value' => '+880 1700-000000',       'group' => 'general'],
            ['key' => 'site_address',    'value' => 'Dhaka, Bangladesh',      'group' => 'general'],
            ['key' => 'currency',        'value' => 'BDT',                   'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => '৳',                     'group' => 'general'],

            // Shipping
            ['key' => 'free_shipping_threshold', 'value' => '2000',  'group' => 'shipping'],
            ['key' => 'default_shipping_fee',    'value' => '120',   'group' => 'shipping'],

            // Payment
            ['key' => 'payment_gateway', 'value' => 'sslcommerz', 'group' => 'payment'],

            // SEO
            ['key' => 'meta_description', 'value' => 'Shop the best products at MyShop.', 'group' => 'seo'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::create($setting);
        }

        $this->command->info('✅ Site settings seeded');
    }
}