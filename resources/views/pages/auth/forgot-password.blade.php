<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
            <flux:icon.shield-exclamation class="mx-auto mb-4 size-10 text-zinc-400 dark:text-zinc-500" />
            <flux:heading size="md" class="mb-2">{{ __('Manual Reset Required') }}</flux:heading>
            <flux:text class="text-sm">
                {{ __('For security reasons, automated password resets have been disabled. Please contact the site administrator to request a password reset for your account.') }}
            </flux:text>
        </div>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
