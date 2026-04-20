<?php
namespace Database\Factories;

use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::inRandomOrder()->first()?->id,
            'brand_id'    => Brand::inRandomOrder()->first()?->id,
            'name'        => ucwords($name),
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 999),
            'description' => fake()->paragraphs(3, true),
            'base_price'  => fake()->randomFloat(2, 100, 50000),
            'is_featured' => fake()->boolean(20),
            'is_active'   => true,
        ];
    }
}