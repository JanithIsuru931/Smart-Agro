<?php

use App\Services\Cart;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.storefront')] #[Title('Your Cart')] class extends Component {
    public function updateQuantity(int $productId, int $quantity): void
    {
        app(Cart::class)->update($productId, max(0, $quantity));
    }

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

<div>
    <flux:heading size="xl" class="mb-8 !font-bold">{{ __('Your Cart') }}</flux:heading>

    @if ($this->lines->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:icon.shopping-cart class="mx-auto mb-4 size-12 text-zinc-400" />
            <flux:heading>{{ __('Your cart is empty') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Looks like you haven\'t added any king coconut yet.') }}</flux:text>
            <flux:button :href="route('home')" variant="primary" class="mt-4" wire:navigate>
                {{ __('Browse Products') }}
            </flux:button>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-3 lg:col-span-2">
                @foreach ($this->lines as $line)
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-emerald-100 to-amber-100 dark:from-emerald-900/40 dark:to-amber-900/40">
                            @if ($line['product']->image)
                                <img src="{{ asset('storage/'.$line['product']->image) }}" alt="{{ $line['product']->name }}" class="absolute inset-0 size-full object-cover">
                            @else
                                <flux:icon.sparkles class="size-10 text-emerald-600 dark:text-emerald-400" />
                            @endif
                        </div>
                        <div class="flex-1">
                            <flux:heading class="!font-semibold">{{ $line['product']->name }}</flux:heading>
                            <flux:text class="text-sm">LKR {{ number_format($line['product']->price, 2) }} {{ __('each') }}</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" icon="minus" wire:click="decrement({{ $line['product']->id }})" />
                            <span class="min-w-8 text-center font-medium">{{ $line['quantity'] }}</span>
                            <flux:button size="sm" icon="plus" wire:click="increment({{ $line['product']->id }})" />
                        </div>
                        <div class="min-w-24 text-right">
                            <flux:heading class="!font-semibold">LKR {{ number_format($line['subtotal'], 2) }}</flux:heading>
                        </div>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $line['product']->id }})" />
                    </div>
                @endforeach
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-24 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="!font-semibold">{{ __('Order Summary') }}</flux:heading>
                    <div class="my-4 flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:text>{{ __('Subtotal') }}</flux:text>
                        <flux:text class="!font-medium">LKR {{ number_format($this->total, 2) }}</flux:text>
                    </div>
                    <div class="mb-4 flex justify-between">
                        <flux:text>{{ __('Delivery') }}</flux:text>
                        <flux:text class="!font-medium">{{ __('Cash on Delivery') }}</flux:text>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:heading class="!font-bold">{{ __('Total') }}</flux:heading>
                        <flux:heading class="!font-bold">LKR {{ number_format($this->total, 2) }}</flux:heading>
                    </div>
                    <flux:button :href="route('storefront.checkout')" variant="primary" class="mt-6 w-full" wire:navigate>
                        {{ __('Proceed to Checkout') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
