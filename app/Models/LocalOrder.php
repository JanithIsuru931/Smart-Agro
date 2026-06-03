<?php

namespace App\Models;

use Database\Factories\LocalOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['order_number', 'customer_name', 'customer_phone', 'customer_address', 'total', 'status', 'notes', 'payment_method', 'payment_status', 'payhere_order_id', 'payhere_payment_id'])]
class LocalOrder extends Model
{
    /** @use HasFactory<LocalOrderFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'delivered', 'cancelled'];

    public const PAYMENT_METHODS = ['cod', 'payhere'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed'];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'LO-'.strtoupper(Str::random(8));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(LocalOrderItem::class);
    }
}
