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

it('places a COD order at checkout and clears the cart', function () {
    $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
    app(Cart::class)->add($product->id, 3);

    Livewire::test('pages::storefront.checkout')
        ->set('customer_name', 'Anura Kumara')
        ->set('customer_phone', '0771234567')
        ->set('customer_address', 'No 42, Galle Road, Colombo 03')
        ->set('payment_method', 'cod')
        ->call('placeOrder');

    expect(LocalOrder::count())->toBe(1)
        ->and(app(Cart::class)->isEmpty())->toBeTrue();

    $order = LocalOrder::first();
    expect((float) $order->total)->toBe(300.00)
        ->and($order->payment_method)->toBe('cod')
        ->and($order->payment_status)->toBe('pending')
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
        ->set('payment_method', 'cod')
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

it('validates payment method is cod or payhere', function () {
    $product = Product::factory()->create();
    app(Cart::class)->add($product->id, 1);

    Livewire::test('pages::storefront.checkout')
        ->set('customer_name', 'Test User')
        ->set('customer_phone', '0771234567')
        ->set('customer_address', '123 Test St')
        ->set('payment_method', 'invalid')
        ->call('placeOrder')
        ->assertHasErrors(['payment_method']);
});

it('stores payment method on COD orders', function () {
    $product = Product::factory()->create(['price' => 500, 'stock' => 5]);
    app(Cart::class)->add($product->id, 1);

    Livewire::test('pages::storefront.checkout')
        ->set('customer_name', 'COD Customer')
        ->set('customer_phone', '0761234567')
        ->set('customer_address', 'Kandy Road, Peradeniya')
        ->set('payment_method', 'cod')
        ->call('placeOrder');

    $order = LocalOrder::first();
    expect($order->payment_method)->toBe('cod')
        ->and($order->payment_status)->toBe('pending')
        ->and($order->payhere_order_id)->toBeNull();
});

it('handles payhere notify for completed checkout', function () {
    config(['services.payhere.merchant_secret' => 'TEST_SECRET']);
    $merchantId = '123456';
    $orderId = 'LO-12345678';
    $payhereAmount = '100.00';
    $payhereCurrency = 'LKR';
    $statusCode = '2';
    $paymentId = '320025000000';
    $merchantSecret = 'TEST_SECRET';

    $localMd5sig = strtoupper(
        md5(
            $merchantId.
            $orderId.
            $payhereAmount.
            $payhereCurrency.
            $statusCode.
            strtoupper(md5($merchantSecret))
        )
    );

    $order = LocalOrder::factory()->create([
        'order_number' => $orderId,
        'payment_method' => 'payhere',
        'payment_status' => 'pending',
    ]);

    $this->post(route('payhere.notify'), [
        'merchant_id' => $merchantId,
        'order_id' => $orderId,
        'payhere_amount' => $payhereAmount,
        'payhere_currency' => $payhereCurrency,
        'status_code' => $statusCode,
        'md5sig' => $localMd5sig,
        'payment_id' => $paymentId,
    ])->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe('paid')
        ->and($order->payhere_payment_id)->toBe($paymentId);
});

it('shows payment method on the order success page', function () {
    $order = LocalOrder::factory()->create([
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $this->get(route('storefront.order.success', ['order' => $order->order_number]))
        ->assertOk()
        ->assertSee('Cash on Delivery');
});

it('shows payhere payment status on the order success page', function () {
    $order = LocalOrder::factory()->create([
        'payment_method' => 'payhere',
        'payment_status' => 'paid',
    ]);

    $this->get(route('storefront.order.success', ['order' => $order->order_number]))
        ->assertOk()
        ->assertSee('Online Payment (PayHere)')
        ->assertSee('Paid');
});
