<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku'           => strtoupper(fake()->unique()->bothify('SKU-####-???')),
            'price'         => fake()->randomFloat(2, 100, 50000),
            'compare_price' => fake()->optional(0.4)->randomFloat(2, 500, 60000),
            'stock_qty'     => fake()->numberBetween(0, 200),
            'is_active'     => true,
        ];
    }
}