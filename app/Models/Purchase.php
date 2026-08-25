<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Purchase extends Model
{
    protected $fillable = [
        'po_number',
        'vendor_ref',
        'branch_id',
        'user_id',
        'material_id',
        'qty_bought',
        'total_cost',
        'supplier_id',
        'purchase_plan_id',
        'status',
        'payment_status',
        'paid_at',
        'paid_by',
        'payment_method',
        'account_id',
        'payment_reference',
        'approved_by',
        'approved_at',
        'approval_notes',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    public static function generatePoNumber(): string
    {
        $dateStr = date('Ymd');
        $random = strtoupper(Str::random(4));
        return "PO-{$dateStr}-{$random}";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchasePlan(): BelongsTo
    {
        return $this->belongsTo(PurchasePlan::class, 'purchase_plan_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
