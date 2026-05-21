<?php

use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Supplier Purchase Log')] class extends Component {
    public ?int $editingId = null;

    #[Validate('required|exists:suppliers,id')]
    public ?int $supplier_id = null;

    #[Validate('required|integer|min:1')]
    public int $quantity = 1;

    #[Validate('required|numeric|min:0')]
    public float $unit_price = 0;

    #[Validate('required|date')]
    public string $purchase_date = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    public function mount(): void
    {
        $this->purchase_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function purchases()
    {
        return SupplierPurchase::with('supplier')->latest('purchase_date')->latest()->get();
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function totalPaid(): float
    {
        return (float) SupplierPurchase::sum('total_paid');
    }

    #[Computed]
    public function totalQuantity(): int
    {
        return (int) SupplierPurchase::sum('quantity');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('purchase-form')->show();
    }

    public function openEdit(int $id): void
    {
        $purchase = SupplierPurchase::findOrFail($id);
        $this->editingId = $purchase->id;
        $this->supplier_id = $purchase->supplier_id;
        $this->quantity = $purchase->quantity;
        $this->unit_price = (float) $purchase->unit_price;
        $this->purchase_date = $purchase->purchase_date->format('Y-m-d');
        $this->notes = $purchase->notes ?? '';
        Flux::modal('purchase-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['total_paid'] = $data['quantity'] * $data['unit_price'];

        if ($this->editingId) {
            SupplierPurchase::findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: __('Purchase updated.'));
        } else {
            SupplierPurchase::create($data);
            Flux::toast(variant: 'success', text: __('Purchase logged.'));
        }

        $this->resetForm();
        Flux::modal('purchase-form')->close();
    }

    public function delete(int $id): void
    {
        SupplierPurchase::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Purchase deleted.'));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->supplier_id = null;
        $this->quantity = 1;
        $this->unit_price = 0;
        $this->purchase_date = now()->format('Y-m-d');
        $this->notes = '';
        $this->resetErrorBag();
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="!font-bold">{{ __('Purchase Log') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Track every payment made to suppliers') }}</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                {{ __('Log Purchase') }}
            </flux:button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:card>
                <flux:text class="text-sm">{{ __('Total Units Bought') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->totalQuantity) }}</flux:heading>
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
                        <th class="px-4 py-3 font-medium">{{ __('Supplier') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Quantity') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Unit Price') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Total Paid (LKR)') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->purchases as $purchase)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $purchase->purchase_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $purchase->supplier->name }}</div>
                                @if ($purchase->notes)
                                    <div class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($purchase->notes, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ number_format($purchase->quantity) }}</td>
                            <td class="px-4 py-3">{{ number_format($purchase->unit_price, 2) }}</td>
                            <td class="px-4 py-3 font-medium">{{ number_format($purchase->total_paid, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" icon="pencil" wire:click="openEdit({{ $purchase->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $purchase->id }})" wire:confirm="{{ __('Delete this purchase record?') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">
                                {{ __('No purchases logged yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal name="purchase-form" class="md:w-[500px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg" class="!font-semibold">
                {{ $editingId ? __('Edit Purchase') : __('Log New Purchase') }}
            </flux:heading>

            <flux:select wire:model="supplier_id" :label="__('Supplier')" required>
                <option value="">{{ __('Select supplier...') }}</option>
                @foreach ($this->suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="quantity" type="number" min="1" :label="__('Quantity (units)')" required />
                <flux:input wire:model="unit_price" type="number" step="0.01" min="0" :label="__('Unit Price (LKR)')" required />
            </div>

            <flux:input wire:model="purchase_date" type="date" :label="__('Purchase Date')" required />
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" x-on:click="$flux.modal('purchase-form').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
