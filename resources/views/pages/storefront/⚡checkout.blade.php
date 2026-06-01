<?php

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

        return $this->redirectRoute('storefront.order.success', ['order' => $order->order_number], navigate: true);
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-8 !font-bold">{{ __('Checkout') }}</flux:heading>

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

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                    <flux:text class="text-sm">
                        <strong>{{ __('Cash on Delivery:') }}</strong>
                        {{ __('Pay when your order arrives. No advance payment needed.') }}
                    </flux:text>
                </div>
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
                        {{ __('Place Order') }}
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</div>
