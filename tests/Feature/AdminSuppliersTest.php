<?php

use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

it('lists suppliers with totals', function () {
    $supplier = Supplier::factory()->create(['name' => 'Saman Coconuts']);
    SupplierPurchase::factory()->for($supplier)->create([
        'quantity' => 100,
        'unit_price' => 50,
        'total_paid' => 5000,
    ]);

    $this->get(route('admin.suppliers'))
        ->assertOk()
        ->assertSee('Saman Coconuts');
});

it('creates a supplier through the admin form', function () {
    Livewire::test('pages::admin.suppliers')
        ->set('name', 'Kandy Coconut Co')
        ->set('phone', '0771234567')
        ->set('location', 'Kandy')
        ->call('save');

    expect(Supplier::where('name', 'Kandy Coconut Co')->exists())->toBeTrue();
});

it('logs a supplier purchase', function () {
    $supplier = Supplier::factory()->create();

    Livewire::test('pages::admin.supplier-purchases')
        ->set('supplier_id', $supplier->id)
        ->set('quantity', 200)
        ->set('unit_price', 55)
        ->set('purchase_date', now()->format('Y-m-d'))
        ->call('save');

    $purchase = SupplierPurchase::first();
    expect($purchase)->not->toBeNull()
        ->and((int) $purchase->quantity)->toBe(200)
        ->and((float) $purchase->total_paid)->toBe(11000.00);
});

it('computes total paid for a supplier', function () {
    $supplier = Supplier::factory()->create();
    SupplierPurchase::factory()->for($supplier)->create(['total_paid' => 1000]);
    SupplierPurchase::factory()->for($supplier)->create(['total_paid' => 2500]);

    expect($supplier->totalPaid())->toBe(3500.00);
});
