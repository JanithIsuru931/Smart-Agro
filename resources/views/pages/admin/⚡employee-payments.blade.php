<?php

use App\Models\Employee;
use App\Models\EmployeePayment;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Employee Salary Log')] class extends Component {
    public ?int $editingId = null;

    #[Validate('required|exists:employees,id')]
    public ?int $employee_id = null;

    #[Validate('required|numeric|min:0')]
    public float $amount = 0;

    #[Validate('required|date')]
    public string $payment_date = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    // Calculate salary modal properties
    public ?int $calc_employee_id = null;

    public string $calc_start_date = '';

    public string $calc_end_date = '';

    public ?string $pay_start_date = null;

    public ?string $pay_end_date = null;

    /** @var array{present_days: int, half_days: int, absent_days: int, total: float, basic_salary: float, attendance_bonus: float, final_total_salary: float, start_date: string, end_date: string, employee_id: int, employee_name: string}|null */
    public ?array $calcResult = null;

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
        $this->calc_start_date = now()->startOfMonth()->toDateString();
        $this->calc_end_date = now()->endOfMonth()->toDateString();
    }

    #[Computed]
    public function payments()
    {
        return EmployeePayment::with('employee')->latest('payment_date')->latest()->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function totalPaid(): float
    {
        return (float) EmployeePayment::sum('amount');
    }

    #[Computed]
    public function totalPayments(): int
    {
        return (int) EmployeePayment::count();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('payment-form')->show();
    }

    public function openEdit(int $id): void
    {
        $payment = EmployeePayment::findOrFail($id);
        $this->editingId = $payment->id;
        $this->employee_id = $payment->employee_id;
        $this->amount = (float) $payment->amount;
        $this->payment_date = $payment->payment_date->format('Y-m-d');
        $this->notes = $payment->notes ?? '';
        Flux::modal('payment-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            EmployeePayment::findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: __('Payment updated.'));
        } else {
            EmployeePayment::create($data);
            Flux::toast(variant: 'success', text: __('Payment logged.'));

            if ($this->pay_start_date && $this->pay_end_date) {
                \App\Models\EmployeeAttendance::where('employee_id', $this->employee_id)
                    ->whereBetween('date', [$this->pay_start_date, $this->pay_end_date])
                    ->where('is_paid', false)
                    ->update(['is_paid' => true]);
            }
        }

        $this->resetForm();
        Flux::modal('payment-form')->close();
    }

    public function delete(int $id): void
    {
        EmployeePayment::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Payment deleted.'));
    }

    // --- Calculate from Attendance ---

    public function openCalculate(): void
    {
        $this->calc_employee_id = null;
        $this->calc_start_date = now()->startOfMonth()->toDateString();
        $this->calc_end_date = now()->endOfMonth()->toDateString();
        $this->calcResult = null;
        Flux::modal('calculate-salary')->show();
    }

    public function calculateSalary(): void
    {
        if (! $this->calc_employee_id) {
            Flux::toast(variant: 'danger', text: __('Please select an employee.'));

            return;
        }

        if (! $this->calc_start_date || ! $this->calc_end_date) {
            Flux::toast(variant: 'danger', text: __('Please select both start and end dates.'));

            return;
        }

        $today = Carbon::today()->toDateString();
        $startDate = Carbon::parse($this->calc_start_date)->startOfDay();
        $endDate = Carbon::parse($this->calc_end_date)->startOfDay();

        if ($startDate->gt($today)) {
            $this->calc_start_date = $today;
            Flux::toast(variant: 'warning', text: __('Start date cannot be a future date. It has been reset to today.'));

            return;
        }

        if ($endDate->gt($today)) {
            $this->calc_end_date = $today;
            Flux::toast(variant: 'warning', text: __('End date cannot be a future date. It has been reset to today.'));

            return;
        }

        if ($endDate->lt($startDate)) {
            Flux::toast(variant: 'danger', text: __('End date must be on or after the start date.'));

            return;
        }

        $employee = Employee::findOrFail($this->calc_employee_id);

        $this->calcResult = $employee->calculateSalaryFromRange($this->calc_start_date, $this->calc_end_date);
    }

    public function applyCalculatedSalary(): void
    {
        if (! $this->calcResult || ! $this->calc_employee_id) {
            return;
        }

        $this->resetForm();
        $this->employee_id = $this->calc_employee_id;
        $this->amount = $this->calcResult['final_total_salary'];
        $this->pay_start_date = $this->calc_start_date;
        $this->pay_end_date = $this->calc_end_date;
        $this->payment_date = now()->format('Y-m-d');
        $this->notes = __('Salary from :start to :end — :present present, :half half-day, :absent absent', [
            'start' => $this->calc_start_date,
            'end' => $this->calc_end_date,
            'present' => $this->calcResult['present_days'],
            'half' => $this->calcResult['half_days'],
            'absent' => $this->calcResult['absent_days'],
        ]);

        Flux::modal('calculate-salary')->close();
        Flux::modal('payment-form')->show();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->amount = 0;
        $this->payment_date = now()->format('Y-m-d');
        $this->notes = '';
        $this->pay_start_date = null;
        $this->pay_end_date = null;
        $this->resetErrorBag();
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="!font-bold">{{ __('Salary Log') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Track every salary payment made to employees') }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button icon="calculator" wire:click="openCalculate">
                    {{ __('Calculate from Attendance') }}
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                    {{ __('Log Payment') }}
                </flux:button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:card>
                <flux:text class="text-sm">{{ __('Total Payments') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->totalPayments) }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Total Paid (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->totalPaid, 2) }}</flux:heading>
            </flux:card>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="text-left text-sm">
                        <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Amount (LKR)') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->payments as $payment)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $payment->employee->name }}</div>
                                @if ($payment->notes)
                                    <div class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($payment->notes, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button size="sm" icon="pencil" wire:click="openEdit({{ $payment->id }})">{{ __('Edit') }}</flux:button>
                                    <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $payment->id }})" wire:confirm="{{ __('Delete this payment record?') }}" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500">
                                {{ __('No salary payments logged yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment Form Modal --}}
    <flux:modal name="payment-form" class="md:w-[500px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg" class="!font-semibold">
                {{ $editingId ? __('Edit Payment') : __('Log New Payment') }}
            </flux:heading>

            <flux:select wire:model="employee_id" :label="__('Employee')" required>
                <option value="">{{ __('Select employee...') }}</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="amount" type="number" step="0.01" min="0" :label="__('Amount (LKR)')" required />
            <flux:input wire:model="payment_date" type="date" :label="__('Payment Date')" required />
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" x-on:click="$flux.modal('payment-form').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Calculate Salary from Attendance Modal --}}
    <flux:modal name="calculate-salary" class="md:w-[550px]">
        <div class="space-y-5">
            <flux:heading size="lg" class="!font-semibold">
                {{ __('Calculate Salary from Attendance') }}
            </flux:heading>

            <flux:select wire:model="calc_employee_id" :label="__('Employee')">
                <option value="">{{ __('Select employee...') }}</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }} — LKR {{ number_format($employee->daily_rate, 2) }}/day</option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="calc_start_date" type="date" :label="__('Start Date')" max="{{ now()->format('Y-m-d') }}" />
                <flux:input wire:model="calc_end_date" type="date" :label="__('End Date')" max="{{ now()->format('Y-m-d') }}" />
            </div>

            <flux:button wire:click="calculateSalary" icon="calculator" class="w-full">
                {{ __('Calculate') }}
            </flux:button>

            @if ($calcResult)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="md" class="mb-3 !font-semibold">{{ __('Salary Report') }}</flux:heading>

                    <div class="grid gap-3 text-sm">
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('Employee ID') }}</span>
                            <span class="font-semibold">{{ $calcResult['employee_id'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('Employee Name') }}</span>
                            <span class="font-semibold">{{ $calcResult['employee_name'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('Start Date') }}</span>
                            <span class="font-semibold">{{ $calcResult['start_date'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('End Date') }}</span>
                            <span class="font-semibold">{{ $calcResult['end_date'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('Total Full Day Present') }}</span>
                            <span class="font-semibold">{{ $calcResult['present_days'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('Basic Salary') }}</span>
                            <span class="font-semibold">LKR {{ number_format($calcResult['basic_salary'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <span class="text-zinc-500">{{ __('Attendance Bonus') }}</span>
                            <span class="font-semibold">LKR {{ number_format($calcResult['attendance_bonus'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/40 dark:bg-emerald-900/20">
                            <span class="font-medium text-emerald-700 dark:text-emerald-400">{{ __('Final Total Salary') }}</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">LKR {{ number_format($calcResult['final_total_salary'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <flux:button wire:click="applyCalculatedSalary" variant="primary" class="w-full" icon="arrow-right">
                    {{ __('Use This Amount & Log Payment') }}
                </flux:button>
            @endif

            <div class="flex justify-end">
                <flux:button type="button" x-on:click="$flux.modal('calculate-salary').close()">{{ __('Close') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
