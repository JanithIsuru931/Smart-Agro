<?php

namespace Database\Seeders;

use App\Models\BulkInquiry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@smartagro.test',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Ensure the products storage directory exists
        $storagePath = storage_path('app/public/products');
        File::ensureDirectoryExists($storagePath);

        $products = [
            [
                'name' => 'King Coconut – Single',
                'description' => 'Farm-fresh Sri Lankan king coconut, naturally sweet and packed with electrolytes. Hand-picked at peak maturity for the best flavour and hydration.',
                'price' => 200.00,
                'stock' => 50,
                'seed_image' => 'king-coconut-single.jpg',
            ],
            [
                'name' => 'King Coconut – Premium Large (15.5″)',
                'description' => 'Extra-large 15.5-inch king coconut, selected for maximum water content and natural sweetness. Ideal for those who want the fullest refreshment in a single fruit.',
                'price' => 250.00,
                'stock' => 50,
                'seed_image' => 'king-coconut-premium-large.png',
            ],
            [
                'name' => 'King Coconut Box – Fresh Pack (6 Pack)',
                'description' => 'Box of six hand-selected king coconuts, carefully wrapped and packed for safe delivery. Sourced directly from Sri Lankan farms, sorted for quality and freshness. Available for both local and bulk orders.',
                'price' => 490.00,
                'stock' => 50,
                'seed_image' => 'king-coconut-fresh-pack.jpg',
            ],
            [
                'name' => 'King Coconut Box – Export Ready (6 Pack)',
                'description' => 'Six premium king coconuts in branded export-grade packaging, each individually wrapped with a drinking straw. Nutritional label included. Perfect for retail resale or as a gift box. Available for local delivery and bulk orders.',
                'price' => 490.00,
                'stock' => 50,
                'seed_image' => 'king-coconut-export-ready.jpg',
            ],
        ];

        foreach ($products as $data) {
            $imagePath = null;

            if (isset($data['seed_image'])) {
                $source = database_path('seeders/images/'.$data['seed_image']);

                if (File::exists($source)) {
                    $extension = pathinfo($data['seed_image'], PATHINFO_EXTENSION);
                    $filename = Str::random(40).'.'.$extension;
                    File::copy($source, $storagePath.'/'.$filename);
                    $imagePath = 'products/'.$filename;
                }
            }

            Product::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'stock' => $data['stock'],
                'slug' => Str::slug($data['name']).'-'.Str::random(5),
                'image' => $imagePath,
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
