<?php

use App\Models\BulkInquiry;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bulk Inquiries')] class extends Component {
    public string $statusFilter = 'all';

    public string $search = '';

    public ?int $viewingId = null;

    public string $adminNotes = '';

    #[Computed]
    public function inquiries()
    {
        return BulkInquiry::query()
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($q) use ($term) {
                    $q->where('reference', 'like', $term)
                        ->orWhere('buyer_name', 'like', $term)
                        ->orWhere('company', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('country', 'like', $term);
                });
            })
            ->latest()
            ->get();
    }

    #[Computed]
    public function viewingInquiry(): ?BulkInquiry
    {
        if (! $this->viewingId) {
            return null;
        }

        return BulkInquiry::find($this->viewingId);
    }

    public function openView(int $id): void
    {
        $this->viewingId = $id;
        $this->adminNotes = $this->viewingInquiry?->admin_notes ?? '';
        Flux::modal('inquiry-view')->show();
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, BulkInquiry::STATUSES, true)) {
            return;
        }

        BulkInquiry::findOrFail($id)->update(['status' => $status]);
        Flux::toast(variant: 'success', text: __('Status updated.'));
    }

    public function saveNotes(): void
    {
        if (! $this->viewingInquiry) {
            return;
        }

        $this->viewingInquiry->update(['admin_notes' => $this->adminNotes]);
        Flux::toast(variant: 'success', text: __('Notes saved.'));
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <flux:heading size="xl" class="!font-bold">{{ __('Bulk Inquiries') }}</flux:heading>
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search reference / buyer / company...') }}"
                    class="flex-1 md:w-72"
                />
                <flux:select wire:model.live="statusFilter" class="w-40 shrink-0">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="new">{{ __('New') }}</option>
                    <option value="contacted">{{ __('Contacted') }}</option>
                    <option value="quoted">{{ __('Quoted') }}</option>
                    <option value="accepted">{{ __('Accepted') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="closed">{{ __('Closed') }}</option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="text-left text-sm">
                        <th class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Buyer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Country') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Quantity') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->inquiries as $inquiry)
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm">{{ $inquiry->reference }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $inquiry->buyer_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $inquiry->company ?: $inquiry->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $inquiry->country }}</td>
                            <td class="px-4 py-3">{{ number_format($inquiry->quantity) }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match($inquiry->status) { 'new' => 'amber', 'contacted' => 'blue', 'quoted' => 'purple', 'accepted' => 'emerald', 'rejected' => 'red', 'closed' => 'zinc', default => 'zinc' }">
                                    {{ ucfirst($inquiry->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $inquiry->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" icon="eye" wire:click="openView({{ $inquiry->id }})">{{ __('View') }}</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">
                                {{ __('No inquiries found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal name="inquiry-view" class="md:w-[600px]">
        @if ($this->viewingInquiry)
            <div class="space-y-4">
                <flux:heading size="lg" class="!font-semibold">{{ __('Inquiry') }} {{ $this->viewingInquiry->reference }}</flux:heading>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="!font-semibold">{{ __('Buyer Information') }}</flux:text>
                    <div class="mt-2 space-y-1 text-sm">
                        <p><strong>{{ __('Name:') }}</strong> {{ $this->viewingInquiry->buyer_name }}</p>
                        @if ($this->viewingInquiry->company)
                            <p><strong>{{ __('Company:') }}</strong> {{ $this->viewingInquiry->company }}</p>
                        @endif
                        <p><strong>{{ __('Email:') }}</strong> <a href="mailto:{{ $this->viewingInquiry->email }}" class="text-emerald-600 hover:underline">{{ $this->viewingInquiry->email }}</a></p>
                        @if ($this->viewingInquiry->phone)
                            <p><strong>{{ __('Phone:') }}</strong> {{ $this->viewingInquiry->phone }}</p>
                        @endif
                        <p><strong>{{ __('Country:') }}</strong> {{ $this->viewingInquiry->country }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text class="!font-semibold">{{ __('Order Details') }}</flux:text>
                    <div class="mt-2 space-y-1 text-sm">
                        <p><strong>{{ __('Quantity:') }}</strong> {{ number_format($this->viewingInquiry->quantity) }} {{ __('units') }}</p>
                        @if ($this->viewingInquiry->shipping_port)
                            <p><strong>{{ __('Port:') }}</strong> {{ $this->viewingInquiry->shipping_port }}</p>
                        @endif
                        @if ($this->viewingInquiry->preferred_delivery_date)
                            <p><strong>{{ __('Preferred Delivery:') }}</strong> {{ $this->viewingInquiry->preferred_delivery_date->format('Y-m-d') }}</p>
                        @endif
                        @if ($this->viewingInquiry->message)
                            <p><strong>{{ __('Message:') }}</strong></p>
                            <p class="whitespace-pre-wrap">{{ $this->viewingInquiry->message }}</p>
                        @endif
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="adminNotes" :label="__('Admin Notes (internal)')" rows="3" />
                    <div class="mt-2 flex justify-end">
                        <flux:button size="sm" wire:click="saveNotes">{{ __('Save Notes') }}</flux:button>
                    </div>
                </div>

                <div>
                    <flux:text class="!font-semibold">{{ __('Update Status') }}</flux:text>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <flux:button size="sm" wire:click="updateStatus({{ $this->viewingInquiry->id }}, 'contacted')">{{ __('Contacted') }}</flux:button>
                        <flux:button size="sm" wire:click="updateStatus({{ $this->viewingInquiry->id }}, 'quoted')">{{ __('Quoted') }}</flux:button>
                        <flux:button size="sm" variant="primary" wire:click="updateStatus({{ $this->viewingInquiry->id }}, 'accepted')">{{ __('Accepted') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="updateStatus({{ $this->viewingInquiry->id }}, 'rejected')">{{ __('Rejected') }}</flux:button>
                        <flux:button size="sm" wire:click="updateStatus({{ $this->viewingInquiry->id }}, 'closed')">{{ __('Closed') }}</flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
