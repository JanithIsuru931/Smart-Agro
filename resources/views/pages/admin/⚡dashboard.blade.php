<?php

use App\Models\BulkInquiry;
use App\Models\EmployeePayment;
use App\Models\LocalOrder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component {
    public string $range = 'all_time';

    public function rangeOptions(): array
    {
        return [
            'last_week' => __('Last Week'),
            'last_month' => __('Last Month'),
            'last_year' => __('Last Year'),
            'all_time' => __('All Time'),
        ];
    }

    #[Computed]
    public function rangeLabel(): string
    {
        return $this->rangeOptions()[$this->range] ?? __('All Time');
    }

    private function applyRange(Builder $query): Builder
    {
        return match ($this->range) {
            'last_week' => $query->where('created_at', '>=', now()->subWeek()->startOfDay()),
            'last_month' => $query->where('created_at', '>=', now()->subMonth()->startOfDay()),
            'last_year' => $query->where('created_at', '>=', now()->subYear()->startOfDay()),
            default => $query,
        };
    }

    #[Computed]
    public function stats(): array
    {
        $orders = $this->applyRange(LocalOrder::query());
        $inquiries = $this->applyRange(BulkInquiry::query());
        $purchases = $this->applyRange(SupplierPurchase::query());

        return [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'pending_orders' => (clone $orders)->where('status', 'pending')->count(),
            'delivered_orders' => (clone $orders)->where('status', 'delivered')->count(),
            'new_inquiries' => (clone $inquiries)->where('status', 'new')->count(),
            'suppliers' => Supplier::where('is_active', true)->count(),
            'total_paid' => (float) (clone $purchases)->sum('total_paid'),
            'total_revenue' => (float) (clone $orders)->whereIn('status', ['confirmed', 'delivered'])->sum('total'),
        ];
    }

    #[Computed]
    public function netRevenue(): array
    {
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        $weeklyRevenue = (float) LocalOrder::whereIn('status', ['confirmed', 'delivered'])
            ->where('created_at', '>=', $weekStart)
            ->sum('total');

        $weeklyPurchases = (float) SupplierPurchase::where('purchase_date', '>=', $weekStart)
            ->sum('total_paid');

        $weeklyEmployeePayments = (float) EmployeePayment::where('payment_date', '>=', $weekStart)
            ->sum('amount');

        $monthlyRevenue = (float) LocalOrder::whereIn('status', ['confirmed', 'delivered'])
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $monthlyPurchases = (float) SupplierPurchase::where('purchase_date', '>=', $monthStart)
            ->sum('total_paid');

        $monthlyEmployeePayments = (float) EmployeePayment::where('payment_date', '>=', $monthStart)
            ->sum('amount');

        return [
            'weekly' => $weeklyRevenue - $weeklyPurchases - $weeklyEmployeePayments,
            'monthly' => $monthlyRevenue - $monthlyPurchases - $monthlyEmployeePayments,
            'weekly_purchases' => $weeklyPurchases,
            'monthly_purchases' => $monthlyPurchases,
            'weekly_employee_payments' => $weeklyEmployeePayments,
            'monthly_employee_payments' => $monthlyEmployeePayments,
        ];
    }

    #[Computed]
    public function recentOrders()
    {
        return $this->applyRange(LocalOrder::query())
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentInquiries()
    {
        return $this->applyRange(BulkInquiry::query())
            ->latest()
            ->take(5)
            ->get();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl" class="!font-bold">{{ __('Admin Dashboard') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Showing :range overview', ['range' => $this->rangeLabel]) }}</flux:text>
            </div>

            <div class="w-full md:w-64">
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Date Range') }}</label>
                <select wire:model.live="range" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    @foreach ($this->rangeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <flux:text class="text-sm">{{ __('Pending Orders') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ $this->stats['pending_orders'] }}</flux:heading>
                <flux:text class="text-xs">{{ __(':n delivered in :range', ['n' => $this->stats['delivered_orders'], 'range' => $this->rangeLabel]) }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('New Inquiries') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ $this->stats['new_inquiries'] }}</flux:heading>
                <flux:text class="text-xs">{{ __('In :range', ['range' => $this->rangeLabel]) }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Revenue (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->stats['total_revenue'], 2) }}</flux:heading>
                <flux:text class="text-xs">{{ __('From confirmed orders in :range', ['range' => $this->rangeLabel]) }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm">{{ __('Paid to Suppliers (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->stats['total_paid'], 2) }}</flux:heading>
                <flux:text class="text-xs">{{ __('Purchases in :range', ['range' => $this->rangeLabel]) }}</flux:text>
                <flux:text class="text-xs">{{ __(':n active suppliers', ['n' => $this->stats['suppliers']]) }}</flux:text>
            </flux:card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:card class="relative overflow-hidden">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-100/50 dark:bg-emerald-900/20"></div>
                <flux:text class="text-sm font-medium">{{ __('Weekly Net Revenue (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold {{ $this->netRevenue['weekly'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $this->netRevenue['weekly'] >= 0 ? '' : '-' }}{{ number_format(abs($this->netRevenue['weekly']), 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Sales minus purchases & employee payments this week') }}</flux:text>
            </flux:card>
            <flux:card class="relative overflow-hidden">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-100/50 dark:bg-emerald-900/20"></div>
                <flux:text class="text-sm font-medium">{{ __('Monthly Net Revenue (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold {{ $this->netRevenue['monthly'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $this->netRevenue['monthly'] >= 0 ? '' : '-' }}{{ number_format(abs($this->netRevenue['monthly']), 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Sales minus purchases & employee payments this month') }}</flux:text>
            </flux:card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:card>
                <flux:text class="text-sm font-medium">{{ __('Paid to Suppliers This Week (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->netRevenue['weekly_purchases'], 2) }}</flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Supplier purchases this week') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm font-medium">{{ __('Paid to Suppliers This Month (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->netRevenue['monthly_purchases'], 2) }}</flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Supplier purchases this month') }}</flux:text>
            </flux:card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:card>
                <flux:text class="text-sm font-medium">{{ __('Employee Payments This Week (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->netRevenue['weekly_employee_payments'], 2) }}</flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Employee payments this week') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm font-medium">{{ __('Employee Payments This Month (LKR)') }}</flux:text>
                <flux:heading size="xl" class="mt-1 !font-bold">{{ number_format($this->netRevenue['monthly_employee_payments'], 2) }}</flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Employee payments this month') }}</flux:text>
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
