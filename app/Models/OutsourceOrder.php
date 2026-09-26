<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsourceOrder extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'customer_unit_price' => 'decimal:2',
        'customer_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'vendor_unit_price' => 'decimal:2',
        'vendor_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'estimated_margin' => 'decimal:2',
        'approved_at' => 'datetime',
        'qc_at' => 'datetime',
        'completed_at' => 'datetime',
        'receipt_printed_at' => 'datetime',
    ];

    public static function generateOrderNumber(): string
    {
        $prefix = 'OUT-' . date('Ymd') . '-';
        $latest = self::where('order_number', 'like', $prefix . '%')->latest('id')->first();
        if (!$latest) {
            return $prefix . '0001';
        }
        $lastSeq = (int) substr($latest->order_number, -4);
        return $prefix . str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function qcUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getMarginPercentAttribute(): float
    {
        if ($this->customer_price <= 0) return 0;
        return round(($this->estimated_margin / $this->customer_price) * 100, 1);
    }

    public function getStatusLabelAttribute(): array
    {
        return match($this->status) {
            'draft_customer' => [
                'label' => '1. Draft Customer',
                'color' => 'bg-amber-100 text-amber-800 border-amber-200',
                'icon' => 'fa-file-invoice'
            ],
            'pending_approval' => [
                'label' => '2. Menunggu ACC Owner',
                'color' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'icon' => 'fa-user-clock'
            ],
            'in_production' => [
                'label' => '3. Sedang Dikerjakan',
                'color' => 'bg-blue-100 text-blue-800 border-blue-200',
                'icon' => 'fa-gears'
            ],
            'qc_passed' => [
                'label' => '4. Barang Sampai / QC Lolos',
                'color' => 'bg-purple-100 text-purple-800 border-purple-200',
                'icon' => 'fa-clipboard-check'
            ],
            'completed' => [
                'label' => '5. Selesai (Closed)',
                'color' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'icon' => 'fa-circle-check'
            ],
            'rejected' => [
                'label' => 'Ditolak Owner',
                'color' => 'bg-rose-100 text-rose-800 border-rose-200',
                'icon' => 'fa-circle-xmark'
            ],
            'cancelled' => [
                'label' => 'Dibatalkan',
                'color' => 'bg-slate-100 text-slate-800 border-slate-200',
                'icon' => 'fa-ban'
            ],
            default => [
                'label' => ucfirst($this->status),
                'color' => 'bg-slate-100 text-slate-800 border-slate-200',
                'icon' => 'fa-circle-info'
            ],
        };
    }
}
