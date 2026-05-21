<?php

use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

it('lists products in admin', function () {
    Product::factory()->create(['name' => 'Test Coconut']);

    $this->get(route('admin.products'))
        ->assertOk()
        ->assertSee('Test Coconut');
});

it('creates a product through the admin form', function () {
    Livewire::test('pages::admin.products')
        ->set('name', 'New King Coconut')
        ->set('description', 'A fresh new product')
        ->set('price', 200)
        ->set('stock', 50)
        ->set('is_active', true)
        ->call('save');

    expect(Product::where('name', 'New King Coconut')->exists())->toBeTrue();
});

it('toggles product visibility', function () {
    $product = Product::factory()->create(['is_active' => true]);

    Livewire::test('pages::admin.products')
        ->call('toggleActive', $product->id);

    expect($product->fresh()->is_active)->toBeFalse();
});

it('validates required fields when creating a product', function () {
    Livewire::test('pages::admin.products')
        ->set('name', '')
        ->set('price', -10)
        ->call('save')
        ->assertHasErrors(['name', 'price']);
});
