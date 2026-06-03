<?php

use App\Models\LocalOrder;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Local Orders')] class extends Component {
    public string $statusFilter = 'all';

    public string $search = '';

    public ?int $viewingId = null;

    public function updatingSearch(): void
    {
        $this->resetErrorBag();
    }

    #[Computed]
    public function orders()
    {
        return LocalOrder::with('items')
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhere('customer_name', 'like', $term)
                        ->orWhere('customer_phone', 'like', $term);
                });
            })
            ->latest()
            ->get();
    }

    #[Computed]
    public function viewingOrder(): ?LocalOrder
    {
        if (! $this->viewingId) {
            return null;
        }

        return LocalOrder::with('items')->find($this->viewingId);
    }

    public function openView(int $id): void
    {
        $this->viewingId = $id;
        Flux::modal('order-view')->show();
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, LocalOrder::STATUSES, true)) {
            return;
        }

        LocalOrder::findOrFail($id)->update(['status' => $status]);
        Flux::toast(variant: 'success', text: __('Order status updated.'));
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <flux:heading size="xl" class="!font-bold">{{ __('Local Orders') }}</flux:heading>
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search order # / name / phone...') }}"
                    class="flex-1 md:w-72"
                />
                <flux:select wire:model.live="statusFilter" class="w-40 shrink-0">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="confirmed">{{ __('Confirmed') }}</option>
                    <option value="delivered">{{ __('Delivered') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="text-left text-sm">
                        <th class="px-4 py-3 font-medium">{{ __('Order #') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Customer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Phone') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Total (LKR)') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Payment') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $order->customer_phone }}</td>
                            <td class="px-4 py-3 font-medium">{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <flux:badge size="sm" :color="$order->payment_method === 'payhere' ? 'blue' : 'zinc'">
                                        {{ $order->payment_method === 'payhere' ? __('PayHere') : __('COD') }}
                                    </flux:badge>
                                    <flux:badge size="sm" :color="match($order->payment_status) { 'paid' => 'emerald', 'failed' => 'red', default => 'amber' }">
                                        {{ ucfirst($order->payment_status) }}
                                    </flux:badge>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match($order->status) { 'pending' => 'amber', 'confirmed' => 'blue', 'delivered' => 'emerald', 'cancelled' => 'red', default => 'zinc' }">
                                    {{ ucfirst($order->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" icon="eye" wire:click="openView({{ $order->id }})">{{ __('View') }}</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">
                                {{ __('No orders found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal name="order-view" class="md:w-[600px]">
        @if ($this->viewingOrder)
            <div class="space-y-4">
                <flux:heading size="lg" class="!font-semibold">{{ __('Order') }} {{ $this->viewingOrder->order_number }}</flux:heading>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="!font-semibold">{{ __('Customer') }}</flux:text>
                    <div class="mt-2 space-y-1 text-sm">
                        <p><strong>{{ __('Name:') }}</strong> {{ $this->viewingOrder->customer_name }}</p>
                        <p><strong>{{ __('Phone:') }}</strong> {{ $this->viewingOrder->customer_phone }}</p>
                        <p><strong>{{ __('Address:') }}</strong> {{ $this->viewingOrder->customer_address }}</p>
                        <p>
                            <strong>{{ __('Payment:') }}</strong>
                            {{ $this->viewingOrder->payment_method === 'payhere' ? __('Online (PayHere)') : __('Cash on Delivery') }}
                            —
                            <flux:badge size="sm" :color="match($this->viewingOrder->payment_status) { 'paid' => 'emerald', 'failed' => 'red', default => 'amber' }">
                                {{ ucfirst($this->viewingOrder->payment_status) }}
                            </flux:badge>
                        </p>
                        @if ($this->viewingOrder->notes)
                            <p><strong>{{ __('Notes:') }}</strong> {{ $this->viewingOrder->notes }}</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="!font-semibold">{{ __('Items') }}</flux:text>
                    <div class="mt-2 space-y-2">
                        @foreach ($this->viewingOrder->items as $item)
                            <div class="flex justify-between text-sm">
                                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span class="font-medium">LKR {{ number_format($item->subtotal, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex justify-between border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <strong>{{ __('Total') }}</strong>
                        <strong>LKR {{ number_format($this->viewingOrder->total, 2) }}</strong>
                    </div>
                </div>

                <div>
                    <flux:text class="!font-semibold">{{ __('Update Status') }}</flux:text>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <flux:button size="sm" wire:click="updateStatus({{ $this->viewingOrder->id }}, 'pending')">{{ __('Pending') }}</flux:button>
                        <flux:button size="sm" wire:click="updateStatus({{ $this->viewingOrder->id }}, 'confirmed')">{{ __('Confirmed') }}</flux:button>
                        <flux:button size="sm" variant="primary" wire:click="updateStatus({{ $this->viewingOrder->id }}, 'delivered')">{{ __('Mark Delivered') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="updateStatus({{ $this->viewingOrder->id }}, 'cancelled')">{{ __('Cancel') }}</flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
