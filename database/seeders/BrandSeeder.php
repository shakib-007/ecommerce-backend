<?php
namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Apple', 'Samsung', 'Sony', 'Nike',
            'Adidas', 'Dell', 'HP', 'Xiaomi',
            'Realme', 'Walton',
        ];

        foreach ($brands as $name) {
            Brand::create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Brands seeded');
    }
}