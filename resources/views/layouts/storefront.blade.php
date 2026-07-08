<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>

<body class="min-h-screen w-full overflow-x-hidden bg-white dark:bg-zinc-950">
    @php
        $cartCount = app(\App\Services\Cart::class)->count();
    @endphp

    <header class="sticky top-0 z-50 w-full border-b border-emerald-500/20 bg-zinc-950/90 shadow-lg shadow-emerald-950/20 backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <a href="{{ route('home') }}" wire:navigate class="group flex items-center justify-center gap-3 md:justify-start">
                <div class="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 via-lime-400 to-amber-300 shadow-lg shadow-emerald-500/25 transition group-hover:scale-105">
                    <flux:icon.sparkles class="size-6 text-zinc-950" />
                </div>

                <div class="leading-tight">
                    <div class="bg-gradient-to-r from-emerald-300 via-lime-200 to-amber-200 bg-clip-text text-2xl font-black tracking-tight text-transparent">
                        Smart Agro
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-400/80">
                        Fresh King Coconut
                    </div>
                </div>
            </a>

            <nav class="flex w-full flex-wrap items-center justify-center gap-2 text-sm font-semibold md:w-auto md:justify-end">
                <a href="{{ route('home') }}" wire:navigate class="rounded-full px-4 py-2 text-zinc-100 transition hover:bg-emerald-500 hover:text-white hover:shadow-lg hover:shadow-emerald-500/25">
                    Shop
                </a>

                <a href="{{ route('bulk.inquiry') }}" wire:navigate class="rounded-full px-4 py-2 text-zinc-100 transition hover:bg-amber-500 hover:text-zinc-950 hover:shadow-lg hover:shadow-amber-500/25">
                    Bulk Orders
                </a>

                <a href="{{ route('about') }}" wire:navigate class="rounded-full px-4 py-2 text-zinc-100 transition hover:bg-lime-500 hover:text-zinc-950 hover:shadow-lg hover:shadow-lime-500/25">
                    About
                </a>

                <a href="{{ route('storefront.cart') }}" wire:navigate
                   class="relative inline-flex size-11 items-center justify-center rounded-full bg-zinc-900 text-zinc-100 ring-1 ring-zinc-700 transition hover:bg-emerald-500 hover:text-white hover:ring-emerald-400 hover:shadow-lg hover:shadow-emerald-500/25"
                   aria-label="Cart">
                    <flux:icon.shopping-cart class="size-6" />

                    @if ($cartCount > 0)
                        <span class="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-amber-400 px-1 text-xs font-black text-zinc-950 ring-2 ring-zinc-950">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate class="rounded-full bg-gradient-to-r from-emerald-500 to-lime-500 px-5 py-2 font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:scale-105">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="rounded-full bg-gradient-to-r from-emerald-500 to-lime-500 px-5 py-2 font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:scale-105">
                        Log in
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl overflow-x-hidden px-4 py-6 sm:px-6 md:py-10 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="mt-12 w-full overflow-x-hidden border-t border-emerald-500/20 bg-zinc-950 py-8">
        <div class="mx-auto w-full max-w-7xl px-4 text-center text-sm text-zinc-400 sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} Smart Agro. Premium King Coconut from Sri Lanka.
        </div>
    </footer>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>