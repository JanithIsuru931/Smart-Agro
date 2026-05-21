<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('customers are redirected to the storefront from the dashboard', function () {
    $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('home'));
});

test('admins are redirected to the admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('admin.dashboard'));
});
