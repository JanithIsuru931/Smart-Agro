<?php

use App\Models\Employee;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Manage Employees')] class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:30|regex:/^07\d{2}\s?\d{3}\s?\d{3}$/')]
    public string $phone = '';

    #[Validate('nullable|string|max:50')]
    public string $id_card = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:2048')]
    public $id_photo = null;

    public ?string $currentIdPhoto = null;

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    #[Validate('required|numeric|min:0')]
    public float $daily_rate = 0;

    #[Validate('nullable|numeric|min:0')]
    public ?float $half_day_rate = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Computed]
    public function employees()
    {
        return Employee::withCount('payments')
            ->withSum('payments', 'amount')
            ->withCount(['attendances as attendance_present_count' => function ($q) {
                $q->where('status', 'present')->whereMonth('date', now()->month)->whereYear('date', now()->year);
            }])
            ->withCount(['attendances as attendance_this_month_count' => function ($q) {
                $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
            }])
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
        $this->id_card = $employee->id_card ?? '';
        $this->currentIdPhoto = $employee->id_photo;
        $this->id_photo = null;
        $this->location = $employee->location ?? '';
        $this->notes = $employee->notes ?? '';
        $this->daily_rate = (float) $employee->daily_rate;
        $this->half_day_rate = $employee->half_day_rate !== null ? (float) $employee->half_day_rate : null;
        $this->is_active = $employee->is_active;
        Flux::modal('employee-form')->show();
    }

    public function save(): void
    {
        $data = collect($this->validate())->except('id_photo')->all();
        $idPhotoPath = $this->id_photo?->store('employees/id_cards', 'public');

        if ($this->editingId) {
            $employee = Employee::findOrFail($this->editingId);
            $oldPhoto = $employee->id_photo;

            if ($idPhotoPath) {
                $data['id_photo'] = $idPhotoPath;
            }

            $employee->update($data);

            if ($idPhotoPath && $oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }

            Flux::toast(variant: 'success', text: __('Employee updated.'));
        } else {
            $data['id_photo'] = $idPhotoPath;
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
        $this->id_card = '';
        $this->id_photo = null;
        $this->currentIdPhoto = null;
        $this->location = '';
        $this->notes = '';
        $this->daily_rate = 0;
        $this->half_day_rate = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <flux:heading size="xl" class="!font-bold">{{ __('Employees') }}</flux:heading>
            <div class="flex gap-2">
                <flux:button :href="route('admin.attendance')" wire:navigate icon="calendar-days">
                    {{ __('Attendance') }}
                </flux:button>
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
                        <th class="px-4 py-3 font-medium">{{ __('ID Card') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Location') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Daily Rate') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Attendance (Month)') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Total Paid (LKR)') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->employees as $employee)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="relative inline-block group">
                                    <div class="font-medium">{{ $employee->name }}</div>
                                    @if ($employee->notes)
                                        <div class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($employee->notes, 50) }}</div>
                                    @endif

                                    <div class="absolute left-0 top-full z-50 mt-2 hidden w-80 rounded-xl border border-zinc-200 bg-white p-3 text-sm text-zinc-700 shadow-xl transition duration-150 ease-in-out group-hover:block dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <div class="font-semibold mb-2">{{ __('Employee details') }}</div>
                                        <div class="space-y-1">
                                            <div><span class="font-medium">{{ __('Phone') }}:</span> {{ $employee->phone ?: '-' }}</div>
                                            <div><span class="font-medium">{{ __('ID Card') }}:</span> {{ $employee->id_card ?: '-' }}</div>
                                            <div><span class="font-medium">{{ __('Location') }}:</span> {{ $employee->location ?: '-' }}</div>
                                            <div><span class="font-medium">{{ __('Daily Rate') }}:</span> {{ number_format($employee->daily_rate, 2) }}</div>
                                            <div><span class="font-medium">{{ __('Status') }}:</span> {{ $employee->is_active ? __('Active') : __('Inactive') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $employee->phone ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->id_card ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $employee->location ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ number_format($employee->daily_rate, 2) }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="emerald">{{ $employee->attendance_present_count ?? 0 }}{{ __('P') }}</flux:badge>
                                <span class="text-xs text-zinc-400">/ {{ $employee->attendance_this_month_count ?? 0 }} {{ __('days') }}</span>
                            </td>
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
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">
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
            <flux:input wire:model="id_card" :label="__('ID Card Number')" :placeholder="__('Optional: NIC / Passport')" />
            @error('id_card')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ID Photo') }}</label>
                <input wire:model="id_photo" type="file" accept="image/*" class="block w-full cursor-pointer rounded-lg border border-zinc-300 bg-white text-sm text-zinc-900 shadow-sm file:mr-4 file:border-0 file:bg-zinc-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:file:bg-zinc-100 dark:file:text-zinc-900 dark:hover:file:bg-zinc-200">
                @error('id_photo')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if ($currentIdPhoto)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <img src="{{ asset('storage/'.$currentIdPhoto) }}" alt="{{ __('Current ID Photo') }}" class="h-16 w-16 rounded-lg object-cover">
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Current ID Photo') }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Upload a new file to replace it.') }}</div>
                        </div>
                    </div>
                @endif
            </div>
            <flux:input wire:model="location" :label="__('Location')" required />
            @error('location')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
            @error('notes')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <flux:input wire:model="daily_rate" type="number" step="0.01" min="0" :label="__('Daily Rate (LKR)')" required />
                    @error('daily_rate')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <flux:input wire:model="half_day_rate" type="number" step="0.01" min="0" :label="__('Half-Day Rate (LKR)')" :placeholder="__('Auto: daily / 2')" />
                    @error('half_day_rate')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <flux:switch wire:model="is_active" :label="__('Active')" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" x-on:click="$flux.modal('employee-form').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
