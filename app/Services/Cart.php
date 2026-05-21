<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    private const SESSION_KEY = 'cart';

    public function add(int $productId, int $quantity = 1): void
    {
        $items = $this->items();
        $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        $this->save($items);
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->items();
        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }
        $this->save($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->items();
        unset($items[$productId]);
        $this->save($items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @return array<int, int> Map of product_id => quantity
     */
    public function items(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    public function isEmpty(): bool
    {
        return empty($this->items());
    }

    /**
     * Get cart with hydrated products and subtotals.
     *
     * @return Collection<int, array{product: Product, quantity: int, subtotal: float}>
     */
    public function lines(): Collection
    {
        $items = $this->items();
        if (empty($items)) {
            return collect();
        }

        return Product::whereIn('id', array_keys($items))
            ->get()
            ->map(fn (Product $product) => [
                'product' => $product,
                'quantity' => $items[$product->id],
                'subtotal' => (float) $product->price * $items[$product->id],
            ]);
    }

    public function total(): float
    {
        return (float) $this->lines()->sum('subtotal');
    }

    private function save(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }
}
