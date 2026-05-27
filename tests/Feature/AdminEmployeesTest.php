<?php

use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

it('lists employees with totals', function () {
    $employee = Employee::factory()->create(['name' => 'Nimal Perera']);
    EmployeePayment::factory()->for($employee)->create([
        'amount' => 45000,
    ]);

    $this->get(route('admin.employees'))
        ->assertOk()
        ->assertSee('Nimal Perera');
});

it('creates an employee through the admin form', function () {
    Livewire::test('pages::admin.employees')
        ->set('name', 'Ruwan Fernando')
        ->set('phone', '0771234567')
        ->set('location', 'Kandy')
        ->call('save');

    expect(Employee::where('name', 'Ruwan Fernando')->exists())->toBeTrue();
});

it('logs an employee payment', function () {
    $employee = Employee::factory()->create();

    Livewire::test('pages::admin.employee-payments')
        ->set('employee_id', $employee->id)
        ->set('amount', 52000)
        ->set('payment_date', now()->format('Y-m-d'))
        ->call('save');

    $payment = EmployeePayment::first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(52000.00);
});

it('computes total paid for an employee', function () {
    $employee = Employee::factory()->create();
    EmployeePayment::factory()->for($employee)->create(['amount' => 1000]);
    EmployeePayment::factory()->for($employee)->create(['amount' => 2500]);

    expect($employee->totalPaid())->toBe(3500.00);
});
