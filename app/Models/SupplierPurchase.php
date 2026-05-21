<?php

namespace App\Models;

use Database\Factories\SupplierPurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_id', 'quantity', 'unit_price', 'total_paid', 'purchase_date', 'notes'])]
class SupplierPurchase extends Model
{
    /** @use HasFactory<SupplierPurchaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
