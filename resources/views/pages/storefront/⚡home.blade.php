<?php

use App\Models\Product;
use App\Services\Cart;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.storefront')] #[Title('Shop King Coconut')] class extends Component {
    public function addToCart(int $productId): void
    {
        $product = Product::active()->findOrFail($productId);

        if (! $product->isInStock()) {
            Flux::toast(variant: 'danger', text: __('Sorry, this item is out of stock.'));

            return;
        }

        app(Cart::class)->add($productId);

        Flux::toast(variant: 'success', text: __(':name added to cart.', ['name' => $product->name]));
    }

    #[Computed]
    public function products()
    {
        return Product::active()->latest()->get();
    }
}; ?>

<div>
    <section class="mb-10 rounded-2xl bg-gradient-to-br from-emerald-50 to-amber-50 p-8 dark:from-emerald-950/30 dark:to-amber-950/30 md:p-12">
        <div class="max-w-2xl">
            <flux:heading size="xl" class="!text-4xl !font-bold md:!text-5xl">
                {{ __('Fresh King Coconut Delivered to Your Door') }}
            </flux:heading>
            <flux:text class="mt-4 text-lg">
                {{ __('Naturally hydrating. Locally sourced. Pay on delivery.') }}
            </flux:text>
        </div>
    </section>

    <section>
        <flux:heading size="lg" class="mb-6 !font-semibold">{{ __('Our Products') }}</flux:heading>

        @if ($this->products->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                <flux:text>{{ __('No products available yet.') }}</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->products as $product)
                    <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex aspect-square items-center justify-center bg-gradient-to-br from-emerald-100 to-amber-100 dark:from-emerald-900/40 dark:to-amber-900/40">
                            @if ($product->image)
                                <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="size-full object-cover">
                            @else
                                <flux:icon.sparkles class="size-20 text-emerald-600 dark:text-emerald-400" />
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <flux:heading class="!font-semibold">{{ $product->name }}</flux:heading>
                            <flux:text class="mt-1 line-clamp-2 text-sm">{{ $product->description }}</flux:text>
                            <div class="mt-auto pt-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <flux:heading size="lg" class="!font-bold">LKR {{ number_format($product->price, 2) }}</flux:heading>
                                    @if ($product->stock <= 5 && $product->stock > 0)
                                        <flux:badge color="amber" size="sm">{{ __('Only :n left', ['n' => $product->stock]) }}</flux:badge>
                                    @elseif ($product->stock === 0)
                                        <flux:badge color="zinc" size="sm">{{ __('Out of stock') }}</flux:badge>
                                    @endif
                                </div>
                                <flux:button
                                    variant="primary"
                                    icon="shopping-cart"
                                    wire:click="addToCart({{ $product->id }})"
                                    :disabled="$product->stock === 0"
                                    class="w-full"
                                >
                                    {{ __('Add to Cart') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
