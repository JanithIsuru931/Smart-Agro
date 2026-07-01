<?php

use App\Jobs\SendOrderNotificationToOwner;
use App\Models\LocalOrder;
use App\Models\LocalOrderItem;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.storefront')] #[Title('Checkout')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $customer_name = '';

    #[Validate('required|string|max:30|regex:/^07\d{2}\s?\d{3}\s?\d{3}$/')]
    public string $customer_phone = '';

    #[Validate('required|string|max:500')]
    public string $customer_address = '';

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    #[Validate('required|in:cod,payhere')]
    public string $payment_method = 'cod';

    /** @var array<string, mixed>|null PayHere form data for redirect */
    public ?array $payhereFormData = null;

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

    public function placeOrder()
    {
        $this->validate();

        $cart = app(Cart::class);
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return $this->redirectRoute('storefront.cart', navigate: true);
        }

        $order = DB::transaction(function () use ($lines) {
            $order = LocalOrder::create([
                'customer_name' => $this->customer_name,
                'customer_phone' => $this->customer_phone,
                'customer_address' => $this->customer_address,
                'total' => $lines->sum('subtotal'),
                'status' => 'pending',
                'payment_method' => $this->payment_method,
                'payment_status' => 'pending',
                'notes' => $this->notes ?: null,
            ]);

            foreach ($lines as $line) {
                LocalOrderItem::create([
                    'local_order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['product']->price,
                    'subtotal' => $line['subtotal'],
                ]);

                Product::where('id', $line['product']->id)->decrement('stock', $line['quantity']);
            }

            return $order;
        });

        app(Cart::class)->clear();

        // Send WhatsApp notification to owner
        SendOrderNotificationToOwner::dispatch($order);

        // COD: redirect straight to success
        if ($this->payment_method === 'cod') {
            return $this->redirectRoute('storefront.order.success', ['order' => $order->order_number], navigate: true);
        }

        // PayHere: build form data and submit via JS
        $merchantId = config('services.payhere.merchant_id');
        $merchantSecret = config('services.payhere.merchant_secret');
        $amount = number_format((float) $order->total, 2, '.', '');
        $currency = 'LKR';

        $hash = strtoupper(
            md5(
                $merchantId.
                $order->order_number.
                $amount.
                $currency.
                strtoupper(md5($merchantSecret))
            )
        );

        $nameParts = explode(' ', $this->customer_name, 2);

        $payhereUrl = config('services.payhere.sandbox', true)
            ? 'https://sandbox.payhere.lk/pay/checkout'
            : 'https://www.payhere.lk/pay/checkout';

        $this->payhereFormData = [
            'action_url' => $payhereUrl,
            'merchant_id' => $merchantId,
            'return_url' => route('storefront.payhere.return', ['order' => $order->order_number]),
            'cancel_url' => route('storefront.payhere.cancel', ['order' => $order->order_number]),
            'notify_url' => route('payhere.notify'),
            'order_id' => $order->order_number,
            'items' => $lines->map(fn ($l) => $l['product']->name.' x'.$l['quantity'])->implode(', '),
            'currency' => $currency,
            'amount' => $amount,
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'email' => 'customer@smartagro.lk',
            'phone' => $this->customer_phone,
            'address' => $this->customer_address,
            'city' => 'Sri Lanka',
            'country' => 'Sri Lanka',
            'hash' => $hash,
        ];

        $this->dispatch('submit-payhere');
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-8 !font-bold">{{ __('Checkout') }}</flux:heading>

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/30">
            <flux:text class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</flux:text>
        </div>
    @endif

    @if ($this->lines->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:text>{{ __('Your cart is empty.') }}</flux:text>
            <flux:button :href="route('home')" variant="primary" class="mt-4" wire:navigate>
                {{ __('Browse Products') }}
            </flux:button>
        </div>
    @else
        <form wire:submit="placeOrder" class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-4 !font-semibold">{{ __('Delivery Details') }}</flux:heading>

                    <div class="space-y-4">
                        <flux:input wire:model="customer_name" :label="__('Full Name')" required autocomplete="name" />
                        @error('customer_name')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <flux:input wire:model="customer_phone" :label="__('Phone Number')" type="tel" required autocomplete="tel" placeholder="07X XXX XXXX" />
                        @error('customer_phone')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <flux:textarea wire:model="customer_address" :label="__('Delivery Address')" required rows="3" />
                        @error('customer_address')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <flux:textarea wire:model="notes" :label="__('Order Notes (optional)')" rows="2" />
                        @error('notes')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Payment Method Selection --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-4 !font-semibold">{{ __('Payment Method') }}</flux:heading>

                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border-2 p-4 transition-all {{ $payment_method === 'cod' ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <input type="radio" wire:model.live="payment_method" value="cod" class="sr-only" />
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $payment_method === 'cod' ? 'bg-emerald-500 text-white' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                <flux:icon.banknotes class="size-5" />
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold {{ $payment_method === 'cod' ? 'text-emerald-700 dark:text-emerald-300' : '' }}">{{ __('Cash on Delivery') }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pay when your order arrives. No advance payment needed.') }}</p>
                            </div>
                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 {{ $payment_method === 'cod' ? 'border-emerald-500 bg-emerald-500' : 'border-zinc-300 dark:border-zinc-600' }}">
                                @if ($payment_method === 'cod')
                                    <flux:icon.check class="size-4 text-white" />
                                @endif
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border-2 p-4 transition-all {{ $payment_method === 'payhere' ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <input type="radio" wire:model.live="payment_method" value="payhere" class="sr-only" />
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $payment_method === 'payhere' ? 'bg-blue-500 text-white' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                <flux:icon.credit-card class="size-5" />
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold {{ $payment_method === 'payhere' ? 'text-blue-700 dark:text-blue-300' : '' }}">{{ __('Pay Online (Card / Bank)') }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Secure payment via PayHere. Visa, Mastercard, and bank transfers.') }}</p>
                            </div>
                            <div class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 {{ $payment_method === 'payhere' ? 'border-blue-500 bg-blue-500' : 'border-zinc-300 dark:border-zinc-600' }}">
                                @if ($payment_method === 'payhere')
                                    <flux:icon.check class="size-4 text-white" />
                                @endif
                            </div>
                        </label>
                    </div>

                    @error('payment_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Payment Info --}}
                @if ($payment_method === 'cod')
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                        <flux:text class="text-sm">
                            <strong>{{ __('Cash on Delivery:') }}</strong>
                            {{ __('Pay when your order arrives. No advance payment needed.') }}
                        </flux:text>
                    </div>
                @else
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/30">
                        <flux:text class="text-sm">
                            <strong>{{ __('Online Payment:') }}</strong>
                            {{ __('You will be redirected to PayHere\'s secure checkout to complete your payment.') }}
                        </flux:text>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-24 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="!font-semibold">{{ __('Order Summary') }}</flux:heading>
                    <div class="my-4 space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        @foreach ($this->lines as $line)
                            <div class="flex justify-between text-sm">
                                <span>{{ $line['product']->name }} × {{ $line['quantity'] }}</span>
                                <span class="font-medium">LKR {{ number_format($line['subtotal'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:heading class="!font-bold">{{ __('Total') }}</flux:heading>
                        <flux:heading class="!font-bold">LKR {{ number_format($this->total, 2) }}</flux:heading>
                    </div>
                    <flux:button type="submit" variant="primary" class="mt-6 w-full">
                        @if ($payment_method === 'cod')
                            {{ __('Place Order (Cash on Delivery)') }}
                        @else
                            {{ __('Pay Online via PayHere') }}
                        @endif
                    </flux:button>
                </div>
            </div>
        </form>
    @endif

    {{-- Hidden PayHere redirect form --}}
    @if ($payhereFormData)
        <div x-data x-init="$refs.payhereForm.submit()">
            <form x-ref="payhereForm" method="POST" action="{{ $payhereFormData['action_url'] }}" class="hidden">
                @foreach (collect($payhereFormData)->except('action_url') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
            </form>
        </div>
    @endif
</div>
