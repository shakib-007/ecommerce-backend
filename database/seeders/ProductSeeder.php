<?php
namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // --- Smartphone product with Color + Storage variants ---
        $smartphoneCategory = Category::where('slug', 'smartphones')->first();

        $phone = Product::create([
            'category_id' => $smartphoneCategory->id,
            'brand_id' => \App\Models\Brand::where('name', 'Samsung')->first()->id,
            'name' => 'Samsung Galaxy S24',
            'slug' => 'samsung-galaxy-s24',
            'description' => 'The latest Samsung flagship with a stunning display and powerful camera system.',
            'base_price' => 85000,
            'is_featured' => true,
            'is_active' => true,
        ]);

        // Get attribute values we need
        $black = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Color'))
            ->where('value', 'Black')->first();
        $white = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Color'))
            ->where('value', 'White')->first();
        $s128 = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Storage'))
            ->where('value', '128GB')->first();
        $s256 = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Storage'))
            ->where('value', '256GB')->first();

        // Create 4 variants: Black/128, Black/256, White/128, White/256
        $variants = [
            ['color' => $black, 'storage' => $s128, 'price' => 85000, 'sku' => 'SGS24-BLK-128'],
            ['color' => $black, 'storage' => $s256, 'price' => 95000, 'sku' => 'SGS24-BLK-256'],
            ['color' => $white, 'storage' => $s128, 'price' => 85000, 'sku' => 'SGS24-WHT-128'],
            ['color' => $white, 'storage' => $s256, 'price' => 95000, 'sku' => 'SGS24-WHT-256'],
        ];

        foreach ($variants as $v) {
            $variant = ProductVariant::create([
                'product_id' => $phone->id,
                'sku' => $v['sku'],
                'price' => $v['price'],
                'compare_price' => $v['price'] + 5000,
                'stock_qty' => rand(10, 100),
                'is_active' => true,
            ]);

            // Attach attribute values to this variant
            $variant->attributeValues()->attach([
                $v['color']->id,
                $v['storage']->id,
            ]);
        }

        // --- Clothing product with Color + Size variants ---
        $mensCategory = Category::where('slug', 'mens-clothing')->first();

        $shirt = Product::create([
            'category_id' => $mensCategory->id,
            'brand_id' => \App\Models\Brand::where('name', 'Nike')->first()->id,
            'name' => 'Nike Dri-FIT T-Shirt',
            'slug' => 'nike-dri-fit-t-shirt',
            'description' => 'Lightweight, breathable performance t-shirt for everyday wear.',
            'base_price' => 2500,
            'is_featured' => false,
            'is_active' => true,
        ]);

        $red = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Color'))
            ->where('value', 'Red')->first();
        $blue = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Color'))
            ->where('value', 'Blue')->first();

        $sizes = AttributeValue::whereHas('group', fn($q) => $q->where('name', 'Size'))
            ->whereIn('value', ['S', 'M', 'L', 'XL'])
            ->get();

        foreach ([$red, $blue] as $color) {
            foreach ($sizes as $size) {
                $variant = ProductVariant::create([
                    'product_id' => $shirt->id,
                    'sku' => 'NIKE-SHIRT-' . strtoupper($color->value) . '-' . $size->value,
                    'price' => 2500,
                    'stock_qty' => rand(5, 50),
                    'is_active' => true,
                ]);

                $variant->attributeValues()->attach([
                    $color->id,
                    $size->id,
                ]);
            }
        }

        // --- Generate 15 more random products using factory ---
        $leafCategories = Category::whereNotNull('parent_id')->get();

        Product::factory(15)->make()->each(function ($product) use ($leafCategories) {
            $product->category_id = $leafCategories->random()->id;
            $product->save();

            // Each random product gets 2-3 simple variants (just price/stock, no attrs)
            ProductVariant::factory(rand(2, 3))->create([
                'product_id' => $product->id,
            ]);
        });

        $this->command->info('✅ Products seeded');
    }
}