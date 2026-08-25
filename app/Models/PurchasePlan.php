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
        'payment_status',
        'paid_at',
        'paid_by',
        'payment_method',
        'account_id',
        'payment_reference',
        'payment_notes',
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
            'paid_at' => 'datetime',
            'total_estimated_cost' => 'decimal:2',
        ];
    }

    protected $appends = ['supplier_bills'];

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

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
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

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getSupplierBillsAttribute(): array
    {
        $bills = [];
        foreach ($this->items as $item) {
            $suppKey = $item->supplier_id ? 'id_' . $item->supplier_id : ($item->supplier_name ?: 'Tanpa Vendor');
            
            if (!isset($bills[$suppKey])) {
                $suppModel = $item->supplier ?: ($item->supplier_id ? Supplier::find($item->supplier_id) : null);
                if (!$suppModel && $item->supplier_name) {
                    $suppModel = Supplier::where('name', $item->supplier_name)->first();
                }

                $bills[$suppKey] = [
                    'supplier_id' => $suppModel ? $suppModel->id : $item->supplier_id,
                    'supplier_name' => $suppModel ? $suppModel->name : ($item->supplier_name ?: 'Vendor Mandiri / Umum'),
                    'perusahaan' => $suppModel ? $suppModel->perusahaan : null,
                    'kontak' => $suppModel ? $suppModel->kontak : null,
                    'bank_name' => $suppModel ? $suppModel->bank_name : null,
                    'bank_account_number' => $suppModel ? $suppModel->bank_account_number : null,
                    'bank_account_name' => $suppModel ? $suppModel->bank_account_name : null,
                    'total_amount' => 0,
                    'items' => [],
                ];
            }

            $subtotal = (float) ($item->subtotal ?: ($item->qty * $item->estimated_unit_price));
            $bills[$suppKey]['total_amount'] += $subtotal;
            $bills[$suppKey]['items'][] = [
                'material_name' => $item->material_name,
                'qty' => $item->qty,
                'unit_price' => (float) $item->estimated_unit_price,
                'subtotal' => $subtotal,
            ];
        }

        return array_values($bills);
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
