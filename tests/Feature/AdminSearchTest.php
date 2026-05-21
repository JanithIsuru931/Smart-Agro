<?php

use App\Models\BulkInquiry;
use App\Models\LocalOrder;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

it('filters local orders by order number', function () {
    $product = Product::factory()->create();
    $match = LocalOrder::factory()->create(['order_number' => 'LO-FINDME1', 'customer_name' => 'Alice']);
    $miss = LocalOrder::factory()->create(['order_number' => 'LO-OTHER22', 'customer_name' => 'Bob']);
    $match->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'unit_price' => 100, 'subtotal' => 100]);
    $miss->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'unit_price' => 100, 'subtotal' => 100]);

    Livewire::test('pages::admin.orders')
        ->set('search', 'FINDME1')
        ->assertSee('LO-FINDME1')
        ->assertDontSee('LO-OTHER22');
});

it('filters local orders by customer name', function () {
    LocalOrder::factory()->create(['customer_name' => 'Saman Perera']);
    LocalOrder::factory()->create(['customer_name' => 'Kamal Silva']);

    Livewire::test('pages::admin.orders')
        ->set('search', 'Saman')
        ->assertSee('Saman Perera')
        ->assertDontSee('Kamal Silva');
});

it('filters bulk inquiries by reference', function () {
    BulkInquiry::factory()->create(['reference' => 'BI-FINDME99', 'buyer_name' => 'Alpha']);
    BulkInquiry::factory()->create(['reference' => 'BI-NOMATCH1', 'buyer_name' => 'Beta']);

    Livewire::test('pages::admin.inquiries')
        ->set('search', 'FINDME99')
        ->assertSee('BI-FINDME99')
        ->assertDontSee('BI-NOMATCH1');
});

it('filters bulk inquiries by buyer name', function () {
    BulkInquiry::factory()->create(['buyer_name' => 'Hans Müller']);
    BulkInquiry::factory()->create(['buyer_name' => 'Jane Doe']);

    Livewire::test('pages::admin.inquiries')
        ->set('search', 'Müller')
        ->assertSee('Hans Müller')
        ->assertDontSee('Jane Doe');
});
