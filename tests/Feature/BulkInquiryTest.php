<?php

use App\Models\BulkInquiry;
use Livewire\Livewire;

it('renders the bulk inquiry page', function () {
    $this->get(route('bulk.inquiry'))
        ->assertOk()
        ->assertSee('Bulk Export Orders');
});

it('submits a bulk inquiry', function () {
    Livewire::test('pages::bulk-inquiry')
        ->set('buyer_name', 'Hans Müller')
        ->set('company', 'EuroFresh GmbH')
        ->set('email', 'hans@eurofresh.de')
        ->set('country', 'Germany')
        ->set('quantity', 5000)
        ->set('shipping_port', 'Port of Hamburg')
        ->set('message', 'Looking for monthly shipments.')
        ->call('submit')
        ->assertSet('submitted', true);

    $inquiry = BulkInquiry::first();
    expect($inquiry)->not->toBeNull()
        ->and($inquiry->buyer_name)->toBe('Hans Müller')
        ->and((int) $inquiry->quantity)->toBe(5000)
        ->and($inquiry->status)->toBe('new')
        ->and($inquiry->reference)->toStartWith('BI-');
});

it('validates required fields on bulk inquiry', function () {
    Livewire::test('pages::bulk-inquiry')
        ->call('submit')
        ->assertHasErrors(['buyer_name', 'email', 'country']);
});

it('enforces minimum quantity for bulk inquiry', function () {
    Livewire::test('pages::bulk-inquiry')
        ->set('buyer_name', 'Test')
        ->set('email', 'test@example.com')
        ->set('country', 'France')
        ->set('quantity', 50)
        ->call('submit')
        ->assertHasErrors(['quantity']);
});
