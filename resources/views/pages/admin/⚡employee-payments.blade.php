<?php

use App\Models\Employee;
use App\Models\EmployeePayment;
use Flux\Flux;
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

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
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
        }

        $this->resetForm();
        Flux::modal('payment-form')->close();
    }

    public function delete(int $id): void
    {
        EmployeePayment::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Payment deleted.'));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->amount = 0;
        $this->payment_date = now()->format('Y-m-d');
        $this->notes = '';
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
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                {{ __('Log Payment') }}
            </flux:button>
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
</div>
