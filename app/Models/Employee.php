<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'phone', 'id_card', 'id_photo', 'location', 'notes', 'daily_rate', 'half_day_rate', 'is_active'])]
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
     * @return array{present_days: int, half_days: int, absent_days: int, total: float, basic_salary: float, attendance_bonus: float, final_total_salary: float, start_date: string, end_date: string, employee_id: int, employee_name: string}
     */
    public function calculateSalary(string $year, string $month): array
    {
        $startDate = Carbon::create((int) $year, (int) $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create((int) $year, (int) $month, 1)->endOfMonth()->toDateString();

        return $this->calculateSalaryFromRange($startDate, $endDate);
    }

    /**
     * Calculate salary for a selected date range and add an attendance bonus when applicable.
     *
     * @return array{present_days: int, half_days: int, absent_days: int, total: float, basic_salary: float, attendance_bonus: float, final_total_salary: float, start_date: string, end_date: string, employee_id: int, employee_name: string}
     */
    public function calculateSalaryFromRange(string $startDate, string $endDate): array
    {
        $startCarbon = Carbon::parse($startDate)->startOfDay();
        $endCarbon = Carbon::parse($endDate)->startOfDay();

        $attendances = $this->attendances()
            ->where('is_paid', false)
            ->whereDate('date', '>=', $startCarbon->toDateString())
            ->whereDate('date', '<=', $endCarbon->toDateString())
            ->orderBy('date')
            ->get();

        $presentDays = $attendances->where('status', 'present')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $absentDays = $attendances->where('status', 'absent')->count();

        $basicSalary = ($presentDays * (float) $this->daily_rate)
            + ($halfDays * $this->effectiveHalfDayRate());

        $attendanceBonus = $this->calculateAttendanceBonus($attendances, $startDate, $endDate);

        return [
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'total' => round($basicSalary, 2),
            'basic_salary' => round($basicSalary, 2),
            'attendance_bonus' => round($attendanceBonus, 2),
            'final_total_salary' => round($basicSalary + $attendanceBonus, 2),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $this->id,
            'employee_name' => $this->name,
        ];
    }

    private function calculateAttendanceBonus($attendances, string $startDate, string $endDate): float
    {
        $statusByDate = $attendances->mapWithKeys(function ($attendance) {
            $date = $attendance->date instanceof Carbon
                ? $attendance->date->toDateString()
                : Carbon::parse($attendance->date)->toDateString();

            return [$date => $attendance->status];
        });

        $currentDate = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->startOfDay();
        $currentStreak = 0;

        while ($currentDate->lte($endDateCarbon)) {
            $dateString = $currentDate->toDateString();
            $status = $statusByDate->get($dateString);

            if ($status === 'present') {
                $currentStreak++;

                if ($currentStreak >= 7) {
                    return 1000.0;
                }
            } else {
                $currentStreak = 0;
            }

            $currentDate->addDay();
        }

        return 0.0;
    }
}
