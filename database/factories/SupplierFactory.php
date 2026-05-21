<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->randomElement(['Kurunegala', 'Gampaha', 'Puttalam', 'Negombo', 'Chilaw']),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
