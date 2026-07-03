<?php

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Employee Attendance')] class extends Component {
    public string $selectedDate = '';

    public string $viewMonth = '';

    /** @var array<int, string> */
    public array $statuses = [];

    /** @var array<int, string> */
    public array $notes = [];

    /** @var array<int, bool> Employees whose attendance is already saved in DB */
    public array $saved = [];

    /** @var array<int, bool> Saved employees that admin has unlocked for editing */
    public array $editing = [];

    public string $tab = 'mark';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->viewMonth = now()->format('Y-m');
        $this->loadAttendanceForDate();
    }

    #[Computed]
    public function activeEmployees()
    {
        return Employee::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function todayStats(): array
    {
        $date = $this->selectedDate;

        return [
            'present' => EmployeeAttendance::whereDate('date', $date)->where('status', 'present')->count(),
            'half_day' => EmployeeAttendance::whereDate('date', $date)->where('status', 'half_day')->count(),
            'absent' => EmployeeAttendance::whereDate('date', $date)->where('status', 'absent')->count(),
            'unmarked' => Employee::where('is_active', true)->count()
                - EmployeeAttendance::whereDate('date', $date)->count(),
        ];
    }

    #[Computed]
    public function monthlyData(): array
    {
        [$year, $month] = explode('-', $this->viewMonth);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);

        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        $attendances = EmployeeAttendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy(fn ($a) => $a->employee_id.'-'.$a->date->format('d'));

        $rows = [];
        foreach ($employees as $employee) {
            $days = [];
            $presentCount = 0;
            $halfDayCount = 0;
            $absentCount = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $key = $employee->id.'-'.str_pad($d, 2, '0', STR_PAD_LEFT);
                $record = $attendances->get($key)?->first();
                $status = $record?->status ?? null;
                $days[$d] = $status;

                if ($status === 'present') {
                    $presentCount++;
                } elseif ($status === 'half_day') {
                    $halfDayCount++;
                } elseif ($status === 'absent') {
                    $absentCount++;
                }
            }

            $rows[] = [
                'employee' => $employee,
                'days' => $days,
                'present' => $presentCount,
                'half_day' => $halfDayCount,
                'absent' => $absentCount,
            ];
        }

        return [
            'rows' => $rows,
            'daysInMonth' => $daysInMonth,
            'year' => $year,
            'month' => $month,
        ];
    }

    public function updatedSelectedDate(): void
    {
        $today = now()->startOfDay();
        $selectedDate = Carbon::parse($this->selectedDate)->startOfDay();

        if ($selectedDate->gt($today)) {
            $this->selectedDate = $today->toDateString();
            Flux::toast(variant: 'warning', text: __('Future attendance dates are not allowed.'));
        }

        $this->loadAttendanceForDate();
    }

    public function loadAttendanceForDate(): void
    {
        $existing = EmployeeAttendance::whereDate('date', $this->selectedDate)
            ->pluck('status', 'employee_id')
            ->toArray();

        $existingNotes = EmployeeAttendance::whereDate('date', $this->selectedDate)
            ->pluck('notes', 'employee_id')
            ->toArray();

        $this->statuses = [];
        $this->notes = [];
        $this->saved = [];
        $this->editing = [];

        foreach ($this->activeEmployees as $employee) {
            $this->statuses[$employee->id] = $existing[$employee->id] ?? '';
            $this->notes[$employee->id] = $existingNotes[$employee->id] ?? '';
            $this->saved[$employee->id] = isset($existing[$employee->id]);
        }
    }

    public function enableEdit(int $employeeId): void
    {
        $this->editing[$employeeId] = true;
    }

    public function markAllPresent(): void
    {
        foreach ($this->activeEmployees as $employee) {
            $isSaved = $this->saved[$employee->id] ?? false;
            $isEditing = $this->editing[$employee->id] ?? false;

            if (! $isSaved || $isEditing) {
                if (empty($this->statuses[$employee->id])) {
                    $this->statuses[$employee->id] = 'present';
                }
            }
        }
    }

    public function saveAttendance(): void
    {
        $today = now()->startOfDay();
        $selectedDate = Carbon::parse($this->selectedDate)->startOfDay();

        if ($selectedDate->gt($today)) {
            $this->selectedDate = $today->toDateString();
            $this->loadAttendanceForDate();
            Flux::toast(variant: 'warning', text: __('Future attendance dates are not allowed.'));

            return;
        }

        $saved = 0;

        foreach ($this->statuses as $employeeId => $status) {
            if (empty($status)) {
                continue;
            }

            $attendance = EmployeeAttendance::where('employee_id', $employeeId)
                ->whereDate('date', $this->selectedDate)
                ->first();

            if ($attendance) {
                $attendance->update([
                    'status' => $status,
                    'notes' => $this->notes[$employeeId] ?? null,
                ]);
            } else {
                EmployeeAttendance::create([
                    'employee_id' => $employeeId,
                    'date' => $this->selectedDate,
                    'status' => $status,
                    'notes' => $this->notes[$employeeId] ?? null,
                ]);
            }

            $saved++;
        }

        if ($saved > 0) {
            Flux::toast(variant: 'success', text: __(':count attendance records saved.', ['count' => $saved]));
        } else {
            Flux::toast(variant: 'warning', text: __('No attendance marked. Select a status for at least one employee.'));
        }

        $this->loadAttendanceForDate();
        unset($this->todayStats, $this->monthlyData);
    }

    public function clearAttendance(int $employeeId): void
    {
        EmployeeAttendance::where('employee_id', $employeeId)
            ->whereDate('date', $this->selectedDate)
            ->delete();

        $this->statuses[$employeeId] = '';
        $this->notes[$employeeId] = '';

        Flux::toast(variant: 'success', text: __('Attendance cleared.'));
        unset($this->todayStats, $this->monthlyData);
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" class="!font-bold">{{ __('Attendance') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Track daily attendance for all employees') }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button
                    :variant="$tab === 'mark' ? 'primary' : 'ghost'"
                    icon="clipboard-document-check"
                    wire:click="$set('tab', 'mark')"
                >
                    {{ __('Mark Attendance') }}
                </flux:button>
                <flux:button
                    :variant="$tab === 'calendar' ? 'primary' : 'ghost'"
                    icon="calendar-days"
                    wire:click="$set('tab', 'calendar')"
                >
                    {{ __('Monthly View') }}
                </flux:button>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid gap-4 md:grid-cols-4">
            <flux:card>
                <flux:text class="text-sm">{{ __('Present') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold text-emerald-600">{{ $this->todayStats['present'] }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Half Day') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold text-amber-500">{{ $this->todayStats['half_day'] }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Absent') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold text-red-500">{{ $this->todayStats['absent'] }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Unmarked') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold text-zinc-400">{{ $this->todayStats['unmarked'] }}</flux:heading>
            </flux:card>
        </div>

        {{-- Tab: Mark Attendance --}}
        @if ($tab === 'mark')
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="w-full md:w-64">
                    <flux:input wire:model.live="selectedDate" type="date" :label="__('Date')" max="{{ now()->format('Y-m-d') }}" />
                </div>
                <div class="flex gap-2">
                    <flux:button wire:click="markAllPresent" icon="check-circle">
                        {{ __('Mark All Present') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="saveAttendance" icon="check">
                        {{ __('Save Attendance') }}
                    </flux:button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr class="text-left text-sm">
                            <th class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Daily Rate (LKR)') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Notes') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->activeEmployees as $employee)
                            @php
                                $isSaved = $saved[$employee->id] ?? false;
                                $isEditing = $editing[$employee->id] ?? false;
                                $isLocked = $isSaved && !$isEditing;
                                $status = $statuses[$employee->id] ?? '';
                            @endphp
                            <tr class="transition-colors {{ $status === 'present' ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : ($status === 'absent' ? 'bg-red-50/50 dark:bg-red-900/10' : ($status === 'half_day' ? 'bg-amber-50/50 dark:bg-amber-900/10' : '')) }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium">{{ $employee->name }}</div>
                                        @if ($isSaved && !$isEditing)
                                            <flux:badge size="sm" color="emerald">{{ __('Saved') }}</flux:badge>
                                        @elseif ($isEditing)
                                            <flux:badge size="sm" color="amber">{{ __('Editing') }}</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $employee->location ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ number_format($employee->daily_rate, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if ($isLocked)
                                        {{-- Show read-only status badge --}}
                                        @if ($status === 'present')
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-emerald-500 bg-emerald-100 px-3 py-1.5 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Present') }}</span>
                                        @elseif ($status === 'half_day')
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-amber-500 bg-amber-100 px-3 py-1.5 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Half Day') }}</span>
                                        @elseif ($status === 'absent')
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-red-500 bg-red-100 px-3 py-1.5 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Absent') }}</span>
                                        @endif
                                    @else
                                        {{-- Editable radio buttons --}}
                                        <div class="flex gap-2">
                                            <label class="flex cursor-pointer items-center gap-1 rounded-lg border px-3 py-1.5 text-sm transition-colors {{ $status === 'present' ? 'border-emerald-500 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'border-zinc-200 dark:border-zinc-700' }}">
                                                <input type="radio" wire:model.live="statuses.{{ $employee->id }}" value="present" class="sr-only" />
                                                {{ __('Present') }}
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-1 rounded-lg border px-3 py-1.5 text-sm transition-colors {{ $status === 'half_day' ? 'border-amber-500 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'border-zinc-200 dark:border-zinc-700' }}">
                                                <input type="radio" wire:model.live="statuses.{{ $employee->id }}" value="half_day" class="sr-only" />
                                                {{ __('Half Day') }}
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-1 rounded-lg border px-3 py-1.5 text-sm transition-colors {{ $status === 'absent' ? 'border-red-500 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'border-zinc-200 dark:border-zinc-700' }}">
                                                <input type="radio" wire:model.live="statuses.{{ $employee->id }}" value="absent" class="sr-only" />
                                                {{ __('Absent') }}
                                            </label>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($isLocked)
                                        <span class="text-sm text-zinc-500">{{ $notes[$employee->id] ?: '-' }}</span>
                                    @else
                                        <input type="text" wire:model="notes.{{ $employee->id }}" placeholder="{{ __('Optional note...') }}" class="w-full rounded-lg border border-zinc-200 bg-transparent px-2 py-1 text-sm outline-none focus:border-zinc-400 dark:border-zinc-700" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($isLocked)
                                        <flux:button size="sm" icon="pencil" wire:click="enableEdit({{ $employee->id }})">{{ __('Edit') }}</flux:button>
                                    @elseif (!empty($status))
                                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearAttendance({{ $employee->id }})" wire:confirm="{{ __('Clear attendance for this employee on this date?') }}" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">
                                    {{ __('No active employees. Add employees first.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Tab: Monthly Calendar View --}}
        @if ($tab === 'calendar')
            <div class="flex items-end gap-4">
                <div class="w-full md:w-64">
                    <flux:input wire:model.live="viewMonth" type="month" :label="__('Month')" />
                </div>
            </div>

            @php $monthly = $this->monthlyData; @endphp

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-left font-medium dark:bg-zinc-800">{{ __('Employee') }}</th>
                            @for ($d = 1; $d <= $monthly['daysInMonth']; $d++)
                                <th class="px-1 py-2 text-center font-medium">{{ $d }}</th>
                            @endfor
                            <th class="px-2 py-2 text-center font-medium text-emerald-600">{{ __('P') }}</th>
                            <th class="px-2 py-2 text-center font-medium text-amber-500">{{ __('H') }}</th>
                            <th class="px-2 py-2 text-center font-medium text-red-500">{{ __('A') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($monthly['rows'] as $row)
                            <tr>
                                <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium whitespace-nowrap dark:bg-zinc-800">
                                    {{ $row['employee']->name }}
                                </td>
                                @for ($d = 1; $d <= $monthly['daysInMonth']; $d++)
                                    <td class="px-1 py-2 text-center">
                                        @if ($row['days'][$d] === 'present')
                                            <span class="inline-block size-5 rounded-full bg-emerald-500 text-white leading-5" title="{{ __('Present') }}">✓</span>
                                        @elseif ($row['days'][$d] === 'half_day')
                                            <span class="inline-block size-5 rounded-full bg-amber-400 text-white leading-5" title="{{ __('Half Day') }}">½</span>
                                        @elseif ($row['days'][$d] === 'absent')
                                            <span class="inline-block size-5 rounded-full bg-red-500 text-white leading-5" title="{{ __('Absent') }}">✗</span>
                                        @else
                                            <span class="inline-block size-5 rounded-full bg-zinc-100 text-zinc-400 leading-5 dark:bg-zinc-700" title="{{ __('Not Marked') }}">–</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="px-2 py-2 text-center font-semibold text-emerald-600">{{ $row['present'] }}</td>
                                <td class="px-2 py-2 text-center font-semibold text-amber-500">{{ $row['half_day'] }}</td>
                                <td class="px-2 py-2 text-center font-semibold text-red-500">{{ $row['absent'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap gap-4 text-sm text-zinc-500">
                <div class="flex items-center gap-1.5">
                    <span class="inline-block size-4 rounded-full bg-emerald-500"></span>
                    {{ __('Present') }}
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block size-4 rounded-full bg-amber-400"></span>
                    {{ __('Half Day') }}
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block size-4 rounded-full bg-red-500"></span>
                    {{ __('Absent') }}
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block size-4 rounded-full bg-zinc-200 dark:bg-zinc-700"></span>
                    {{ __('Not Marked') }}
                </div>
            </div>
        @endif
    </div>
</div>
