<?php

namespace Database\Factories;

use App\Models\BulkInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BulkInquiry>
 */
class BulkInquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'quantity' => fake()->numberBetween(1000, 50000),
            'shipping_port' => fake()->city().' Port',
            'preferred_delivery_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'message' => fake()->paragraph(),
            'status' => 'new',
            'admin_notes' => null,
        ];
    }
}
