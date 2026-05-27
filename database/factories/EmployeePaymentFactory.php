<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeePayment>
 */
class EmployeePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'amount' => fake()->randomFloat(2, 25000, 85000),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
