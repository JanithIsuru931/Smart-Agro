<?php

namespace Database\Factories;

use App\Models\LocalOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocalOrder>
 */
class LocalOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_address' => fake()->address(),
            'total' => fake()->randomFloat(2, 200, 5000),
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
