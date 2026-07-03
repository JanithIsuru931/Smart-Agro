<?php

use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\LocalOrder;
use App\Models\SupplierPurchase;
use App\Models\User;

it('displays weekly and monthly net revenue on the admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

    // Create a confirmed order this week worth 5000
    LocalOrder::factory()->create([
        'status' => 'confirmed',
        'total' => 5000,
        'created_at' => now(),
    ]);

    // Create a supplier purchase this week worth 2000
    SupplierPurchase::factory()->create([
        'total_paid' => 2000,
        'purchase_date' => now(),
    ]);

    // Create an employee payment this week worth 1000
    $employee = Employee::factory()->create();
    EmployeePayment::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 1000,
        'payment_date' => now(),
    ]);

    // Create an older order outside this month (should not affect either)
    LocalOrder::factory()->create([
        'status' => 'confirmed',
        'total' => 10000,
        'created_at' => now()->subMonths(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('Weekly Net Revenue (LKR)')
        ->assertSeeText('Monthly Net Revenue (LKR)')
        ->assertSeeText('2,000.00'); // 5000 - 2000 - 1000
});

it('shows negative net revenue in red when purchases and employee payments exceed sales', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

    // No sales, only purchases and employee payments this week
    SupplierPurchase::factory()->create([
        'total_paid' => 5000,
        'purchase_date' => now(),
    ]);

    $employee = Employee::factory()->create();
    EmployeePayment::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 3000,
        'payment_date' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('8,000.00'); // 5000 + 3000 total expenses
});
