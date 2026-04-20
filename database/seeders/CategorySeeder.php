<?php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics' => [
                'Smartphones', 'Laptops', 'Tablets',
                'Headphones', 'Smart Watches',
            ],
            'Clothing' => [
                'Men\'s Clothing', 'Women\'s Clothing',
                'Kids\' Clothing', 'Footwear',
            ],
            'Home & Living' => [
                'Furniture', 'Kitchen', 'Bedding', 'Lighting',
            ],
            'Sports & Outdoors' => [
                'Fitness Equipment', 'Outdoor Gear', 'Sportswear',
            ],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::create([
                'name'       => $parentName,
                'slug'       => Str::slug($parentName),
                'is_active'  => true,
                'sort_order' => 0,
            ]);

            foreach ($children as $index => $childName) {
                Category::create([
                    'parent_id'  => $parent->id,
                    'name'       => $childName,
                    'slug'       => Str::slug($childName),
                    'is_active'  => true,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->command->info('✅ Categories seeded');
    }
}