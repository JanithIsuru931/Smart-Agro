<?php

use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Manage Products')] class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public float $price = 0;

    #[Validate('required|integer|min:0')]
    public int $stock = 0;

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:2048')]
    public $image = null;

    public ?string $currentImage = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Computed]
    public function products()
    {
        return Product::latest()->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('product-form')->show();
    }

    public function openEdit(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->description = $product->description ?? '';
        $this->price = (float) $product->price;
        $this->stock = $product->stock;
        $this->image = null;
        $this->currentImage = $product->image;
        $this->is_active = $product->is_active;
        Flux::modal('product-form')->show();
    }

    public function save(): void
    {
        $data = collect($this->validate())->except('image')->all();
        $imagePath = $this->image?->store('products', 'public');

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $oldImage = $product->image;

            if ($imagePath) {
                $data['image'] = $imagePath;
            }

            $product->update($data);

            if ($imagePath && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            Flux::toast(variant: 'success', text: __('Product updated.'));
        } else {
            $data['slug'] = Str::slug($data['name']).'-'.Str::random(5);
            $data['image'] = $imagePath;
            Product::create($data);
            Flux::toast(variant: 'success', text: __('Product created.'));
        }

        $this->resetForm();
        Flux::modal('product-form')->close();
    }

    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        Flux::toast(variant: 'success', text: __('Product visibility updated.'));
    }

    public function delete(int $id): void
    {
        try {
            Product::findOrFail($id)->delete();
            Flux::toast(variant: 'success', text: __('Product deleted.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Cannot delete product with existing orders. Deactivate it instead.'));
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->price = 0;
        $this->stock = 0;
        $this->image = null;
        $this->currentImage = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }
}; ?>

<div class="p-6">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <flux:heading size="xl" class="!font-bold">{{ __('Products') }}</flux:heading>
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                {{ __('Add Product') }}
            </flux:button>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="text-left text-sm">
                        <th class="px-4 py-3 font-medium">{{ __('Image') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Price (LKR)') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Stock') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->products as $product)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($product->image)
                                    <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-lg border border-dashed border-zinc-300 text-[10px] font-medium uppercase tracking-wide text-zinc-400 dark:border-zinc-600">
                                        {{ __('No image') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $product->name }}</div>
                                <div class="text-xs text-zinc-500">{{ Str::limit($product->description, 60) }}</div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($product->price, 2) }}</td>
                            <td class="px-4 py-3">{{ $product->stock }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$product->is_active ? 'emerald' : 'zinc'">
                                    {{ $product->is_active ? __('Active') : __('Hidden') }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" icon="pencil" wire:click="openEdit({{ $product->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="toggleActive({{ $product->id }})">
                                    {{ $product->is_active ? __('Hide') : __('Show') }}
                                </flux:button>
                                <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $product->id }})" wire:confirm="{{ __('Delete this product?') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">
                                {{ __('No products yet. Click "Add Product" to create one.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal name="product-form" class="md:w-[500px]">
        <form wire:submit="save" class="space-y-4" enctype="multipart/form-data">
            <flux:heading size="lg" class="!font-semibold">
                {{ $editingId ? __('Edit Product') : __('Add Product') }}
            </flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Price (LKR)')" required />
                <flux:input wire:model="stock" type="number" min="0" :label="__('Stock')" required />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Item Image') }}</label>
                <input wire:model="image" type="file" accept="image/*" class="block w-full cursor-pointer rounded-lg border border-zinc-300 bg-white text-sm text-zinc-900 shadow-sm file:mr-4 file:border-0 file:bg-zinc-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:file:bg-zinc-100 dark:file:text-zinc-900 dark:hover:file:bg-zinc-200">
                @error('image')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if ($editingId && $currentImage)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <img src="{{ asset('storage/'.$currentImage) }}" alt="{{ $name }}" class="h-16 w-16 rounded-lg object-cover">
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Current image') }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Upload a new file to replace it.') }}</div>
                        </div>
                    </div>
                @endif
            </div>
            <flux:switch wire:model="is_active" :label="__('Visible to customers')" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" x-on:click="$flux.modal('product-form').close()">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
