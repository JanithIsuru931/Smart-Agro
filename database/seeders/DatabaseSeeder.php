<?php

namespace Database\Seeders;

use App\Models\BulkInquiry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@kokosip.test',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $products = [
            [
                'name' => 'Fresh King Coconut',
                'description' => 'Naturally sweet, hand-picked king coconut. Hydrating and refreshing.',
                'price' => 150.00,
                'stock' => 200,
            ],
            [
                'name' => 'Premium King Coconut (Large)',
                'description' => 'Extra-large premium king coconut. Perfect for sharing.',
                'price' => 220.00,
                'stock' => 80,
            ],
            [
                'name' => 'King Coconut 6-Pack',
                'description' => 'Six fresh king coconuts at a bundle price. Great for parties.',
                'price' => 800.00,
                'stock' => 40,
            ],
            [
                'name' => 'Bottled King Coconut Water (500ml)',
                'description' => 'Pure king coconut water in a sealed bottle. No added sugar.',
                'price' => 250.00,
                'stock' => 120,
            ],
        ];

        foreach ($products as $data) {
            Product::create([
                ...$data,
                'slug' => Str::slug($data['name']),
                'is_active' => true,
            ]);
        }

        Supplier::factory()
            ->count(4)
            ->create()
            ->each(function (Supplier $supplier) {
                SupplierPurchase::factory()
                    ->count(rand(2, 5))
                    ->for($supplier)
                    ->create();
            });

        BulkInquiry::factory()->count(3)->create();
    }
}
