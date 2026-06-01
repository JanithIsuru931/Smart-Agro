<?php

use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Manage Employees')] class extends Component {
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:30|regex:/^07\d{2}\s?\d{3}\s?\d{3}$/')]
    public string $phone = '';

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Computed]
    public function employees()
    {
        return Employee::withCount('payments')
            ->withSum('payments', 'amount')
            ->latest()
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('employee-form')->show();
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId = $employee->id;
        $this->name = $employee->name;
        $this->phone = $employee->phone ?? '';
        $this->location = $employee->location ?? '';
        $this->notes = $employee->notes ?? '';
        $this->is_active = $employee->is_active;
        Flux::modal('employee-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: __('Employee updated.'));
        } else {
            Employee::create($data);
            Flux::toast(variant: 'success', text: __('Employee added.'));
        }

        $this->resetForm();
        Flux::modal('employee-form')->close();
    }

    public function delete(int $id): void
    {
        try {
            Employee::findOrFail($id)->delete();
            Flux::toast(variant: 'success', text: __('Employee deleted.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Failed to delete employee.'));
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = '';
        $this->location = '';
        $this->notes = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <flux:heading size="xl" class="!font-bold">{{ __('Employees') }}</flux:heading>
            <div class="flex gap-2">
                <flux:button :href="route('admin.employee-payments')" wire:navigate>
                    {{ __('Salary Log') }}
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                    {{ __('Add Employee') }}
                </flux:button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="text-left text-sm">
                        <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Phone') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Location') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Payments') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Total Paid (LKR)') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->employees as $employee)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $employee->name }}</div>
                                @if ($employee->notes)
                                    <div class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($employee->notes, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $employee->phone ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->location ?: '-' }}</td>
                            <td class="px-4 py-3">{{ number_format($employee->payments_count ?? 0) }}</td>
                            <td class="px-4 py-3">{{ number_format($employee->payments_sum_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$employee->is_active ? 'emerald' : 'zinc'">
                                    {{ $employee->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" icon="pencil" wire:click="openEdit({{ $employee->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $employee->id }})" wire:confirm="{{ __('Delete this employee? Salary history will also be removed.') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">
                                {{ __('No employees yet. Click "Add Employee" to create one.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal name="employee-form" class="md:w-[500px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg" class="!font-semibold">
                {{ $editingId ? __('Edit Employee') : __('Add Employee') }}
            </flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required />
            @error('name')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <flux:input wire:model="phone" :label="__('Phone')" type="tel" required />
            @error('phone')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <flux:input wire:model="location" :label="__('Location')" required />
            @error('location')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
            @error('notes')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <flux:switch wire:model="is_active" :label="__('Active')" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" x-on:click="$flux.modal('employee-form').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
