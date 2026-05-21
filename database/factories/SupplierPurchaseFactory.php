<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierPurchase>
 */
class SupplierPurchaseFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(50, 500);
        $unitPrice = fake()->randomFloat(2, 40, 90);

        return [
            'supplier_id' => Supplier::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_paid' => $quantity * $unitPrice,
            'purchase_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
