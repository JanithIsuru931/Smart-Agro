<?php

use App\Models\BulkInquiry;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.storefront')] #[Title('Bulk Orders')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $buyer_name = '';

    #[Validate('required|string|max:255')]
    public string $company = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('required|string|max:100')]
    public string $country = '';

    #[Validate('required|integer|min:201')]
    public int $quantity = 250;

    #[Validate('nullable|string|max:255')]
    public string $shipping_port = '';

    #[Validate('nullable|date|after:today')]
    public string $preferred_delivery_date = '';

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    public bool $submitted = false;

    public string $reference = '';

    public function submit(): void
    {
        $data = $this->validate();

        $inquiry = BulkInquiry::create([
            ...$data,
            'preferred_delivery_date' => $data['preferred_delivery_date'] ?: null,
            'status' => 'new',
        ]);

        $this->reference = $inquiry->reference;
        $this->submitted = true;

        Flux::toast(variant: 'success', text: __('Inquiry submitted. We\'ll reach out within 24 hours.'));
    }
}; ?>

<div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <flux:heading size="xl" class="!font-bold">{{ __('Bulk Export Orders') }}</flux:heading>
            <flux:text class="mt-2 text-lg">
                {{ __('Request a quote for international shipments. Our team will respond within 24 hours.') }}
            </flux:text>
        </div>

        @if ($submitted)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <flux:icon.check-circle class="mx-auto mb-4 size-16 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="xl" class="!font-bold">{{ __('Inquiry Received') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Your reference number is') }} <strong>{{ $reference }}</strong>
                </flux:text>
                <flux:text class="mt-2">
                    {{ __('Our team will email you within 24 hours with pricing and shipping details.') }}
                </flux:text>
                <flux:button :href="route('home')" variant="primary" class="mt-6" wire:navigate>
                    {{ __('Back to Shop') }}
                </flux:button>
            </div>
        @else
            <div class="mb-6 grid overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900/50 md:grid-cols-2">
                <div class="relative hidden min-h-[200px] md:block">
                    <img
                        src="{{ asset('images/coconut-bulk.png') }}"
                        alt="{{ __('Bulk king coconuts ready for export') }}"
                        class="absolute inset-0 size-full object-cover"
                        loading="lazy"
                    >
                </div>
                <div class="bg-emerald-50 p-6 dark:bg-emerald-950/30">
                    <flux:heading class="!font-semibold">{{ __('Why Buy in Bulk From Us?') }}</flux:heading>
                    <ul class="mt-3 space-y-1 text-sm">
                        <li>• {{ __('Direct from Sri Lankan plantations') }}</li>
                        <li>• {{ __('Quality-assured, premium-grade king coconut') }}</li>
                        <li>• {{ __('FOB and CIF pricing available (USD)') }}</li>
                        <li>• {{ __('Minimum order: > 200 units') }}</li>
                    </ul>
                </div>
            </div>

            <form wire:submit="submit" class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 md:p-8">
                <flux:heading size="lg" class="mb-6 !font-semibold">{{ __('Inquiry Details') }}</flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="buyer_name" :label="__('Your Name')" required />
                    <flux:input wire:model="company" :label="__('Company')" required />
                    <flux:input wire:model="email" type="email" :label="__('Email')" required />
                    <flux:input wire:model="phone" type="tel" :label="__('Phone (optional)')" />
                    <flux:input wire:model="country" :label="__('Country')" required />
                    <flux:input wire:model="quantity" type="number" min="201" :label="__('Quantity (units)')" required />
                    <flux:input wire:model="shipping_port" :label="__('Shipping Port (optional)')" placeholder="e.g. Port of Hamburg" />
                    <flux:input wire:model="preferred_delivery_date" type="date" :label="__('Preferred Delivery Date (optional)')" />
                </div>

                <div class="mt-4">
                    <flux:textarea wire:model="message" :label="__('Additional Information (optional)')" rows="4" placeholder="{{ __('Packaging requirements, certifications needed, etc.') }}" />
                </div>

                <div class="mt-6 flex items-center justify-end">
                    <flux:button type="submit" variant="primary">
                        {{ __('Submit Inquiry') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
