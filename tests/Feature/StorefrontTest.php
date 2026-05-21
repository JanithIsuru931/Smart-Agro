<?php

use App\Models\LocalOrder;
use App\Models\Product;
use App\Services\Cart;
use Livewire\Livewire;

it('shows active products on the storefront', function () {
    Product::factory()->create(['name' => 'Visible Coconut', 'is_active' => true]);
    Product::factory()->create(['name' => 'Hidden Coconut', 'is_active' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Visible Coconut')
        ->assertDontSee('Hidden Coconut');
});

it('lets guests add items to the cart', function () {
    $product = Product::factory()->create(['stock' => 10]);

    Livewire::test('pages::storefront.home')
        ->call('addToCart', $product->id);

    expect(app(Cart::class)->count())->toBe(1);
});

it('blocks adding out-of-stock items to the cart', function () {
    $product = Product::factory()->create(['stock' => 0]);

    Livewire::test('pages::storefront.home')
        ->call('addToCart', $product->id);

    expect(app(Cart::class)->count())->toBe(0);
});

it('places an order at checkout and clears the cart', function () {
    $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
    app(Cart::class)->add($product->id, 3);

    Livewire::test('pages::storefront.checkout')
        ->set('customer_name', 'Anura Kumara')
        ->set('customer_phone', '0771234567')
        ->set('customer_address', 'No 42, Galle Road, Colombo 03')
        ->call('placeOrder');

    expect(LocalOrder::count())->toBe(1)
        ->and(app(Cart::class)->isEmpty())->toBeTrue();

    $order = LocalOrder::first();
    expect((float) $order->total)->toBe(300.00)
        ->and($order->items)->toHaveCount(1)
        ->and((int) $order->items->first()->quantity)->toBe(3);
});

it('decrements stock when an order is placed', function () {
    $product = Product::factory()->create(['stock' => 10]);
    app(Cart::class)->add($product->id, 4);

    Livewire::test('pages::storefront.checkout')
        ->set('customer_name', 'Tester')
        ->set('customer_phone', '0712223344')
        ->set('customer_address', 'Some address')
        ->call('placeOrder');

    expect($product->fresh()->stock)->toBe(6);
});

it('validates checkout fields', function () {
    $product = Product::factory()->create();
    app(Cart::class)->add($product->id, 1);

    Livewire::test('pages::storefront.checkout')
        ->call('placeOrder')
        ->assertHasErrors(['customer_name', 'customer_phone', 'customer_address']);
});
