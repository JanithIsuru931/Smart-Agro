<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Fresh King Coconut',
            'Premium King Coconut',
            'Organic King Coconut',
            'Bottled King Coconut Water',
            'King Coconut 6-Pack',
        ]).' '.fake()->unique()->numberBetween(100, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 100, 1500),
            'stock' => fake()->numberBetween(10, 200),
            'image' => null,
            'is_active' => true,
        ];
    }
}
