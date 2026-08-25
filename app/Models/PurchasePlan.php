<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchasePlan extends Model
{
    protected $fillable = [
        'plan_number',
        'branch_id',
        'user_id',
        'title',
        'target_date',
        'total_estimated_cost',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_notes',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'total_estimated_cost' => 'decimal:2',
        ];
    }

    public static function generatePlanNumber(): string
    {
        $dateStr = date('Ym');
        $random = strtoupper(Str::random(4));
        return "PLAN-{$dateStr}-{$random}";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchasePlanItem::class, 'purchase_plan_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'purchase_plan_id');
    }

    public function isWaitingApproval(): bool
    {
        return $this->status === 'waiting_owner_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved_by_owner' || $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected_by_owner';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft Plan',
            'waiting_owner_approval' => 'Menunggu ACC Owner (RFQ)',
            'approved_by_owner' => 'Disetujui Owner (PO Terbit)',
            'rejected_by_owner' => 'Ditolak Owner',
            'completed' => 'Selesai Diterima Gudang',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
