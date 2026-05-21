<?php

namespace App\Models;

use Database\Factories\BulkInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'buyer_name', 'company', 'email', 'phone', 'country',
    'quantity', 'shipping_port', 'preferred_delivery_date', 'message',
    'status', 'admin_notes',
])]
class BulkInquiry extends Model
{
    /** @use HasFactory<BulkInquiryFactory> */
    use HasFactory;

    public const STATUSES = ['new', 'contacted', 'quoted', 'accepted', 'rejected', 'closed'];

    protected static function booted(): void
    {
        static::creating(function (self $inquiry) {
            if (empty($inquiry->reference)) {
                $inquiry->reference = 'BI-'.strtoupper(Str::random(8));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'preferred_delivery_date' => 'date',
        ];
    }
}
