<?php

use App\Models\BulkInquiry;
use App\Models\LocalOrder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        return [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'pending_orders' => LocalOrder::where('status', 'pending')->count(),
            'delivered_orders' => LocalOrder::where('status', 'delivered')->count(),
            'new_inquiries' => BulkInquiry::where('status', 'new')->count(),
            'suppliers' => Supplier::where('is_active', true)->count(),
            'total_paid' => SupplierPurchase::sum('total_paid'),
            'total_revenue' => LocalOrder::whereIn('status', ['confirmed', 'delivered'])->sum('total'),
        ];
    }

    #[Computed]
    public function recentOrders()
    {
        return LocalOrder::latest()->take(5)->get();
    }

    #[Computed]
    public function recentInquiries()
    {
        return BulkInquiry::latest()->take(5)->get();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <flux:heading size="xl" class="!font-bold">{{ __('Admin Dashboard') }}</flux:heading>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <flux:text class="text-sm">{{ __('Pending Orders') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ $this->stats['pending_orders'] }}</flux:heading>
                <flux:text class="text-xs">{{ __(':n delivered total', ['n' => $this->stats['delivered_orders']]) }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('New Inquiries') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ $this->stats['new_inquiries'] }}</flux:heading>
                <flux:text class="text-xs">{{ __('Awaiting your response') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Revenue (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->stats['total_revenue'], 2) }}</flux:heading>
                <flux:text class="text-xs">{{ __('From confirmed orders') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Paid to Suppliers (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->stats['total_paid'], 2) }}</flux:heading>
                <flux:text class="text-xs">{{ __(':n active suppliers', ['n' => $this->stats['suppliers']]) }}</flux:text>
            </flux:card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg" class="!font-semibold">{{ __('Recent Local Orders') }}</flux:heading>
                    <flux:button size="sm" :href="route('admin.orders')" wire:navigate>{{ __('View All') }}</flux:button>
                </div>
                @if ($this->recentOrders->isEmpty())
                    <flux:text>{{ __('No orders yet.') }}</flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($this->recentOrders as $order)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div>
                                    <p class="font-medium">{{ $order->order_number }}</p>
                                    <p class="text-xs">{{ $order->customer_name }} · {{ $order->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">LKR {{ number_format($order->total, 2) }}</p>
                                    <flux:badge size="sm" :color="match($order->status) { 'pending' => 'amber', 'confirmed' => 'blue', 'delivered' => 'emerald', 'cancelled' => 'red', default => 'zinc' }">
                                        {{ ucfirst($order->status) }}
                                    </flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg" class="!font-semibold">{{ __('Recent Bulk Inquiries') }}</flux:heading>
                    <flux:button size="sm" :href="route('admin.inquiries')" wire:navigate>{{ __('View All') }}</flux:button>
                </div>
                @if ($this->recentInquiries->isEmpty())
                    <flux:text>{{ __('No inquiries yet.') }}</flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($this->recentInquiries as $inquiry)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div>
                                    <p class="font-medium">{{ $inquiry->reference }}</p>
                                    <p class="text-xs">{{ $inquiry->buyer_name }} ({{ $inquiry->country }}) · {{ number_format($inquiry->quantity) }} units</p>
                                </div>
                                <div class="text-right">
                                    <flux:badge size="sm" :color="$inquiry->status === 'new' ? 'amber' : 'zinc'">
                                        {{ ucfirst($inquiry->status) }}
                                    </flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
