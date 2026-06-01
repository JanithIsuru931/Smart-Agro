<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAttendance>
 */
class EmployeeAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeBetween('first day of this month', 'last day of this month')->format('Y-m-d'),
            'status' => fake()->randomElement(['present', 'present', 'present', 'half_day', 'absent']),
            'check_in' => fake()->optional(0.7)->time('H:i'),
            'check_out' => fake()->optional(0.5)->time('H:i'),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function present(): static
    {
        return $this->state(['status' => 'present']);
    }

    public function absent(): static
    {
        return $this->state(['status' => 'absent']);
    }

    public function halfDay(): static
    {
        return $this->state(['status' => 'half_day']);
    }
}
