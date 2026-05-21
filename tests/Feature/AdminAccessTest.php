<?php

use App\Models\User;

it('redirects guests away from the admin dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('blocks customers from accessing the admin dashboard', function () {
    $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows admins to access the admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('detects admin role via isAdmin()', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);

    expect($admin->isAdmin())->toBeTrue()
        ->and($customer->isAdmin())->toBeFalse();
});
