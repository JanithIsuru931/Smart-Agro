<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900">
        @php
            $cartCount = app(\App\Services\Cart::class)->count();
        @endphp

        <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                <flux:icon.sparkles class="size-6 text-emerald-600" />
                <flux:heading size="lg" class="!font-bold">Smart Agro</flux:heading>
            </a>

            <flux:navbar class="-mb-px max-md:hidden ms-6">
                <flux:navbar.item :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                    {{ __('Shop') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('bulk.inquiry')" :current="request()->routeIs('bulk.inquiry')" wire:navigate>
                    {{ __('Bulk Orders') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('about')" :current="request()->routeIs('about')" wire:navigate>
                    {{ __('About') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="space-x-0.5 rtl:space-x-reverse">
                <flux:tooltip :content="__('Cart')" position="bottom">
                    <flux:navbar.item
                        icon="shopping-cart"
                        :href="route('storefront.cart')"
                        :badge="$cartCount > 0 ? $cartCount : null"
                        :label="__('Cart')"
                        wire:navigate
                    />
                </flux:tooltip>

                @auth
                    <flux:navbar.item :href="route('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navbar.item>
                @else
                    <flux:navbar.item :href="route('login')" wire:navigate>
                        {{ __('Log in') }}
                    </flux:navbar.item>
                @endauth
            </flux:navbar>
        </flux:header>

        <main class="container mx-auto px-4 py-8 md:px-6 md:py-12">
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-zinc-200 bg-zinc-50 py-8 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="container mx-auto px-4 text-center text-sm text-zinc-600 dark:text-zinc-400">
                &copy; {{ date('Y') }} Smart Agro. {{ __('Premium King Coconut from Sri Lanka.') }}
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
