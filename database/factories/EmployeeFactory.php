<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'location' => fake()->randomElement(['Kurunegala', 'Gampaha', 'Puttalam', 'Negombo', 'Chilaw']),
            'notes' => fake()->optional()->sentence(),
            'daily_rate' => fake()->randomElement([1500, 1800, 2000, 2500]),
            'is_active' => true,
        ];
    }
}
