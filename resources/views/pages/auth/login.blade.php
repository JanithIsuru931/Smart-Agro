<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />


        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Remember Me & Forgot Password -->
            <div x-data="{ showForgotCard: false }" class="w-full">
                <div class="flex items-center justify-between">
                    <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                    <button type="button" @click="showForgotCard = !showForgotCard" class="text-sm text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                        {{ __('Forgot your password?') }}
                    </button>
                </div>

                <div x-show="showForgotCard" x-collapse style="display: none;" class="mt-4">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <flux:text class="text-sm text-center">
                            {{ __('Please contact the site administrator to request a password reset.') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

    </div>
</x-layouts::auth>
