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

<div class="w-full max-w-full overflow-x-hidden">
    <section class="relative mb-10 w-full overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-950 via-zinc-950 to-amber-950 shadow-2xl">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,0.35),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(245,158,11,0.28),transparent_35%)]"></div>

        <div class="relative grid w-full grid-cols-1 items-center gap-0 md:grid-cols-2">
            <div class="min-w-0 p-6 sm:p-10 md:p-14">
                <div class="mb-4 inline-flex rounded-full bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-300 ring-1 ring-emerald-400/30">
                    Fresh • Natural • Sri Lankan
                </div>

                <flux:heading size="xl" class="!text-4xl !font-black !leading-tight !text-white sm:!text-5xl md:!text-6xl">
                    {{ __('Fresh King Coconut Delivered to Your Door') }}
                </flux:heading>

                <p class="mt-5 max-w-xl text-base leading-7 text-zinc-200 sm:text-lg">
                    {{ __('Naturally hydrating, locally sourced and carefully packed for safe delivery. Premium king coconut for homes, shops and bulk export orders.') }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <flux:button variant="primary" :href="route('bulk.inquiry')" wire:navigate icon-trailing="arrow-right" class="!bg-emerald-500 !text-white hover:!bg-emerald-600">
                        {{ __('Request Bulk Quote') }}
                    </flux:button>

                    <flux:button :href="route('storefront.cart')" wire:navigate icon="shopping-cart" class="!bg-amber-400 !text-zinc-950 hover:!bg-amber-500">
                        {{ __('View Cart') }}
                    </flux:button>
                </div>
            </div>

            <div class="relative h-72 w-full overflow-hidden md:h-[560px]">
                <img
                    src="{{ asset('images/coconut-bulk.png') }}"
                    alt="{{ __('Fresh king coconuts ready for delivery') }}"
                    class="h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/40 to-transparent"></div>
            </div>
        </div>
    </section>

    <section class="mb-10 grid w-full grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5 text-center">
            <div class="text-3xl font-black text-emerald-400">100%</div>
            <div class="mt-1 text-sm text-zinc-300">Fresh Quality</div>
        </div>

        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5 text-center">
            <div class="text-3xl font-black text-amber-400">COD</div>
            <div class="mt-1 text-sm text-zinc-300">Cash on Delivery</div>
        </div>

        <div class="rounded-2xl border border-lime-500/20 bg-lime-500/10 p-5 text-center">
            <div class="text-3xl font-black text-lime-400">24h</div>
            <div class="mt-1 text-sm text-zinc-300">Bulk Quote Reply</div>
        </div>
    </section>

    <section class="w-full overflow-x-hidden">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="lg" class="!text-3xl !font-black !text-white">
                    {{ __('Our Products') }}
                </flux:heading>
                <p class="mt-2 text-sm text-zinc-400">
                    {{ __('Choose fresh king coconut products packed with care.') }}
                </p>
            </div>
        </div>

        @if ($this->products->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-600 p-10 text-center">
                <flux:text>{{ __('No products available yet.') }}</flux:text>
            </div>
        @else
            <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->products as $product)
                    <div class="group min-w-0 overflow-hidden rounded-3xl border border-zinc-700/70 bg-zinc-900/90 shadow-xl transition duration-300 hover:-translate-y-1 hover:border-emerald-400/60 hover:shadow-emerald-900/30">
                        <div class="relative flex aspect-square w-full items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-900 to-amber-900">
                            @if ($product->image)
                                <img
                                    src="{{ asset('storage/'.$product->image) }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                            @else
                                <flux:icon.sparkles class="size-20 text-emerald-400" />
                            @endif

                            <div class="absolute left-4 top-4 rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-emerald-300 backdrop-blur">
                                Fresh
                            </div>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col p-5">
                            <flux:heading class="break-words !text-lg !font-bold !text-white">
                                {{ $product->name }}
                            </flux:heading>

                            <flux:text class="mt-2 line-clamp-2 break-words text-sm !text-zinc-400">
                                {{ $product->description }}
                            </flux:text>

                            <div class="mt-5">
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                    <flux:heading size="lg" class="!text-2xl !font-black !text-amber-400">
                                        LKR {{ number_format($product->price, 2) }}
                                    </flux:heading>

                                    @if ($product->stock <= 5 && $product->stock > 0)
                                        <flux:badge color="amber" size="sm">{{ __('Only :n left', ['n' => $product->stock]) }}</flux:badge>
                                    @elseif ($product->stock === 0)
                                        <flux:badge color="zinc" size="sm">{{ __('Out of stock') }}</flux:badge>
                                    @else
                                        <flux:badge color="emerald" size="sm">{{ __('In Stock') }}</flux:badge>
                                    @endif
                                </div>

                                <flux:button
                                    variant="primary"
                                    icon="shopping-cart"
                                    wire:click="addToCart({{ $product->id }})"
                                    :disabled="$product->stock === 0"
                                    class="w-full !bg-emerald-500 !text-white hover:!bg-emerald-600"
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

    <section class="mt-14 w-full overflow-x-hidden rounded-3xl border border-zinc-700 bg-zinc-900/80 p-5 sm:p-8">
        <flux:heading size="lg" class="mb-6 !text-3xl !font-black !text-white">
            {{ __('From Our Farm to Your Door') }}
        </flux:heading>

        <div class="grid w-full grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-4">
            <div class="min-w-0 overflow-hidden rounded-2xl bg-zinc-800 p-3">
                <img src="{{ asset('images/coconut-packaged.jpg') }}" alt="Premium Packaging" class="aspect-square w-full rounded-xl object-cover" loading="lazy">
                <flux:text class="mt-3 text-center text-sm !font-bold !text-zinc-200">{{ __('Premium Packaging') }}</flux:text>
            </div>

            <div class="min-w-0 overflow-hidden rounded-2xl bg-zinc-800 p-3">
                <img src="{{ asset('images/coconut-raw.jpg') }}" alt="Quality Sorted" class="aspect-square w-full rounded-xl object-cover" loading="lazy">
                <flux:text class="mt-3 text-center text-sm !font-bold !text-zinc-200">{{ __('Quality Sorted') }}</flux:text>
            </div>

            <div class="min-w-0 overflow-hidden rounded-2xl bg-zinc-800 p-3">
                <img src="{{ asset('images/coconut-boxed.jpg') }}" alt="Safe Shipping" class="aspect-square w-full rounded-xl object-cover" loading="lazy">
                <flux:text class="mt-3 text-center text-sm !font-bold !text-zinc-200">{{ __('Safe Shipping') }}</flux:text>
            </div>

            <div class="min-w-0 overflow-hidden rounded-2xl bg-zinc-800 p-3">
                <img src="{{ asset('images/coconut-delivery.webp') }}" alt="Ready to Deliver" class="aspect-square w-full rounded-xl object-cover" loading="lazy">
                <flux:text class="mt-3 text-center text-sm !font-bold !text-zinc-200">{{ __('Ready to Deliver') }}</flux:text>
            </div>
        </div>
    </section>
</div>