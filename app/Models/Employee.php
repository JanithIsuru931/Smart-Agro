<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'location', 'notes', 'daily_rate', 'half_day_rate', 'is_active'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'daily_rate' => 'decimal:2',
            'half_day_rate' => 'decimal:2',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeePayment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function totalPayments(): int
    {
        return (int) $this->payments()->count();
    }

    /**
     * Get the effective half-day rate (defaults to daily_rate / 2 if not set).
     */
    public function effectiveHalfDayRate(): float
    {
        return $this->half_day_rate !== null
            ? (float) $this->half_day_rate
            : (float) $this->daily_rate / 2;
    }

    /**
     * Calculate salary for a given month based on attendance records.
     *
     * @return array{present_days: int, half_days: int, absent_days: int, total: float}
     */
    public function calculateSalary(string $year, string $month): array
    {
        $attendances = $this->attendances()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('is_paid', false)
            ->get();

        $presentDays = $attendances->where('status', 'present')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $absentDays = $attendances->where('status', 'absent')->count();

        $total = ($presentDays * (float) $this->daily_rate)
            + ($halfDays * $this->effectiveHalfDayRate());

        return [
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'total' => round($total, 2),
        ];
    }
}
