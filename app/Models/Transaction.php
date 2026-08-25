<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_number',
    'user_id',
    'customer_name',
    'customer_phone',
    'total_price',
    'total_hpp',
    'payment_method',
    'payment_status',
    'paid_amount',
    'remaining_amount',
    'order_status',
    'due_date',
    'production_notes',
    'branch_id'
])]
class Transaction extends Model
{
    protected $casts = [
        'due_date' => 'date',
        'total_price' => 'decimal:2',
        'total_hpp' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'PAID' || $this->remaining_amount <= 0;
    }

    public function isPartial(): bool
    {
        return $this->payment_status === 'PARTIAL' && $this->remaining_amount > 0;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'UNPAID';
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return match($this->order_status) {
            'in_production' => 'Dalam Pengerjaan (Produksi)',
            'ready' => 'Selesai / Siap Diambil',
            'completed' => 'Selesai & Lunas',
            'cancelled' => 'Dibatalkan',
            default => 'Selesai'
        };
    }
}
