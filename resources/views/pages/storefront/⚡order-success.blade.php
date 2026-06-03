<?php

use App\Models\LocalOrder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.storefront')] #[Title('Order Confirmed')] class extends Component {
    public string $order = '';

    public ?LocalOrder $localOrder = null;

    public function mount(string $order): void
    {
        $this->order = $order;
        $this->localOrder = LocalOrder::where('order_number', $order)->with('items')->firstOrFail();
    }
}; ?>

<div class="mx-auto max-w-2xl">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <flux:icon.check-circle class="mx-auto mb-4 size-16 text-emerald-600 dark:text-emerald-400" />
            <flux:heading size="xl" class="!font-bold">{{ __('Order Confirmed!') }}</flux:heading>
            <flux:text class="mt-2">
                {{ __('Thank you for your order. We\'ll deliver it as soon as possible.') }}
            </flux:text>
            <flux:text class="mt-4">
                <strong>{{ __('Order Number:') }}</strong> {{ $localOrder->order_number }}
            </flux:text>
        </div>

        <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4 !font-semibold">{{ __('Order Details') }}</flux:heading>

            <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                @foreach ($localOrder->items as $item)
                    <div class="flex justify-between">
                        <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                        <span class="font-medium">LKR {{ number_format($item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:heading class="!font-bold">{{ __('Total') }}</flux:heading>
                <flux:heading class="!font-bold">LKR {{ number_format($localOrder->total, 2) }}</flux:heading>
            </div>

            <div class="mt-6 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
                <p><strong>{{ __('Name:') }}</strong> {{ $localOrder->customer_name }}</p>
                <p><strong>{{ __('Phone:') }}</strong> {{ $localOrder->customer_phone }}</p>
                <p><strong>{{ __('Address:') }}</strong> {{ $localOrder->customer_address }}</p>
                <p>
                    <strong>{{ __('Payment:') }}</strong>
                    @if ($localOrder->payment_method === 'payhere')
                        {{ __('Online Payment (PayHere)') }}
                        @if ($localOrder->payment_status === 'paid')
                            <flux:badge size="sm" color="emerald" class="ml-1">{{ __('Paid') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="amber" class="ml-1">{{ __('Processing') }}</flux:badge>
                        @endif
                    @else
                        {{ __('Cash on Delivery') }}
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <flux:button :href="route('home')" variant="primary" wire:navigate>
                {{ __('Continue Shopping') }}
            </flux:button>
        </div>
    </div>
