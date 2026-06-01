<?php

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an attendance record for an employee', function () {
    $employee = Employee::factory()->create(['daily_rate' => 2000]);

    $attendance = EmployeeAttendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-01',
        'status' => 'present',
    ]);

    expect($attendance)
        ->employee_id->toBe($employee->id)
        ->status->toBe('present');
});

it('prevents duplicate attendance for same employee on same date', function () {
    $employee = Employee::factory()->create();

    EmployeeAttendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-01',
        'status' => 'present',
    ]);

    EmployeeAttendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-06-01',
        'status' => 'absent',
    ]);
})->throws(QueryException::class);

it('allows attendance for different employees on the same date', function () {
    $employee1 = Employee::factory()->create();
    $employee2 = Employee::factory()->create();

    $a1 = EmployeeAttendance::create([
        'employee_id' => $employee1->id,
        'date' => '2026-06-01',
        'status' => 'present',
    ]);

    $a2 = EmployeeAttendance::create([
        'employee_id' => $employee2->id,
        'date' => '2026-06-01',
        'status' => 'absent',
    ]);

    expect($a1->exists)->toBeTrue();
    expect($a2->exists)->toBeTrue();
});

it('calculates salary correctly with mixed attendance', function () {
    $employee = Employee::factory()->create([
        'daily_rate' => 2000,
        'half_day_rate' => null,
    ]);

    // 3 present, 2 half-day, 1 absent
    EmployeeAttendance::factory()->present()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-01',
    ]);
    EmployeeAttendance::factory()->present()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-02',
    ]);
    EmployeeAttendance::factory()->present()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-03',
    ]);
    EmployeeAttendance::factory()->halfDay()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-04',
    ]);
    EmployeeAttendance::factory()->halfDay()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-05',
    ]);
    EmployeeAttendance::factory()->absent()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-06',
    ]);

    $result = $employee->calculateSalary('2026', '06');

    // 3 × 2000 + 2 × 1000 + 0 × 0 = 8000
    expect($result['present_days'])->toBe(3);
    expect($result['half_days'])->toBe(2);
    expect($result['absent_days'])->toBe(1);
    expect($result['total'])->toBe(8000.0);
});

it('uses custom half-day rate when set', function () {
    $employee = Employee::factory()->create([
        'daily_rate' => 2000,
        'half_day_rate' => 800,
    ]);

    EmployeeAttendance::factory()->present()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-01',
    ]);
    EmployeeAttendance::factory()->halfDay()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-02',
    ]);

    $result = $employee->calculateSalary('2026', '06');

    // 1 × 2000 + 1 × 800 = 2800
    expect($result['total'])->toBe(2800.0);
});

it('returns zero salary when no attendance exists', function () {
    $employee = Employee::factory()->create(['daily_rate' => 2000]);

    $result = $employee->calculateSalary('2026', '06');

    expect($result['present_days'])->toBe(0);
    expect($result['half_days'])->toBe(0);
    expect($result['absent_days'])->toBe(0);
    expect($result['total'])->toBe(0.0);
});

it('excludes already paid attendance from salary calculation', function () {
    $employee = Employee::factory()->create([
        'daily_rate' => 2000,
        'half_day_rate' => null,
    ]);

    // Unpaid present day
    EmployeeAttendance::factory()->present()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-01', 'is_paid' => false,
    ]);

    // Paid present day
    EmployeeAttendance::factory()->present()->create([
        'employee_id' => $employee->id, 'date' => '2026-06-02', 'is_paid' => true,
    ]);

    $result = $employee->calculateSalary('2026', '06');

    // Only 1 present day should be counted (1 × 2000)
    expect($result['present_days'])->toBe(1);
    expect($result['total'])->toBe(2000.0);
});

it('cascades delete attendance when employee is deleted', function () {
    $employee = Employee::factory()->create();

    for ($i = 1; $i <= 5; $i++) {
        EmployeeAttendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => "2026-06-0{$i}",
        ]);
    }

    expect(EmployeeAttendance::where('employee_id', $employee->id)->count())->toBe(5);

    $employee->delete();

    expect(EmployeeAttendance::where('employee_id', $employee->id)->count())->toBe(0);
});
