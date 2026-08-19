<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['po_number', 'vendor_ref', 'branch_id', 'user_id', 'material_id', 'qty_bought', 'total_cost', 'supplier_id', 'status', 'approved_by', 'approved_at', 'approval_notes', 'verified_at', 'verified_by', 'verification_notes'])]
class Purchase extends Model
{
    public static function generatePoNumber(): string
    {
        $dateStr = date('Ymd');
        $random = strtoupper(\Illuminate\Support\Str::random(4));
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

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
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
