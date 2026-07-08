<?php

use App\Services\Cart;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.storefront')] #[Title('Your Cart')] class extends Component {
    public function increment(int $productId): void
    {
        $cart = app(Cart::class);
        $items = $cart->items();

        $cart->update($productId, ($items[$productId] ?? 0) + 1);
    }

    public function decrement(int $productId): void
    {
        $cart = app(Cart::class);
        $items = $cart->items();

        $cart->update($productId, max(0, ($items[$productId] ?? 0) - 1));
    }

    public function removeItem(int $productId): void
    {
        app(Cart::class)->remove($productId);

        Flux::toast(variant: 'success', text: __('Item removed from cart.'));
    }

    #[Computed]
    public function lines()
    {
        return app(Cart::class)->lines();
    }

    #[Computed]
    public function total(): float
    {
        return app(Cart::class)->total();
    }
}; ?>

<div class="w-full max-w-full overflow-x-hidden">
    <flux:heading size="xl" class="mb-6 !text-3xl !font-bold sm:!text-4xl">
        {{ __('Your Cart') }}
    </flux:heading>

    @if ($this->lines->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
            <flux:icon.shopping-cart class="mx-auto mb-4 size-12 text-zinc-400" />
            <flux:heading>{{ __('Your cart is empty') }}</flux:heading>

            <div class="mt-6">
                <flux:button variant="primary" :href="route('home')" wire:navigate>
                    {{ __('Continue Shopping') }}
                </flux:button>
            </div>
        </div>
    @else
        <div class="grid w-full grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-4 lg:col-span-2">
                @foreach ($this->lines as $line)
                    <div class="w-full overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex w-full flex-col gap-4">
                            <div class="flex w-full gap-3">
                                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                    @if ($line['product']->image)
                                        <img
                                            src="{{ asset('storage/'.$line['product']->image) }}"
                                            alt="{{ $line['product']->name }}"
                                            class="h-full w-full object-cover"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center">
                                            <flux:icon.sparkles class="size-9 text-emerald-500" />
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <flux:heading class="break-words !text-base !font-semibold">
                                        {{ $line['product']->name }}
                                    </flux:heading>

                                    <flux:text class="mt-1 text-sm">
                                        LKR {{ number_format($line['product']->price, 2) }} each
                                    </flux:text>
                                </div>
                            </div>

                            <div class="flex w-full flex-wrap items-center justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="outline" icon="minus" wire:click="decrement({{ $line['product']->id }})" />

                                    <span class="min-w-8 text-center font-semibold">
                                        {{ $line['quantity'] }}
                                    </span>

                                    <flux:button size="sm" variant="outline" icon="plus" wire:click="increment({{ $line['product']->id }})" />
                                </div>

                                <div class="text-sm font-bold whitespace-nowrap sm:text-base">
                                    LKR {{ number_format($line['subtotal'], 2) }}
                                </div>

                                <flux:button size="sm" variant="danger" icon="trash" wire:click="removeItem({{ $line['product']->id }})" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="min-w-0">
                <div class="w-full rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading class="mb-5 !font-semibold">
                        {{ __('Order Summary') }}
                    </flux:heading>

                    <div class="space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span>{{ __('Subtotal') }}</span>
                            <span class="font-medium whitespace-nowrap">LKR {{ number_format($this->total, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span>{{ __('Delivery') }}</span>
                            <span class="text-right font-medium">{{ __('Cash on Delivery') }}</span>
                        </div>

                        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-4 text-base font-bold">
                                <span>{{ __('Total') }}</span>
                                <span class="whitespace-nowrap">LKR {{ number_format($this->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <flux:button class="mt-6 w-full" variant="primary" :href="route('storefront.checkout')" wire:navigate>
                        {{ __('Proceed to Checkout') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>