<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'material_id',
    'material_roll_id',
    'product_name_snapshot',
    'unit_snapshot',
    'base_hpp_snapshot',
    'qty_ordered',
    'selling_price',
    'click_charge',
    'fixed_length_m',
    'custom_width_cm',
    'area_m2',
    'cut_length_m',
    'roll_width_snapshot',
    'offcut_width_cm',
    'dimension_text',
    'production_status',
    'printed_at',
    'printed_by',
    'print_batch_id',
    'roll_area_used_m2',
    'offcut_area_m2',
    'is_vendor_job',
    'vendor_name',
    'vendor_cost',
    'shipping_cost',
    'vendor_notes'
])]
class TransactionDetail extends Model
{
    protected $casts = [
        'is_vendor_job' => 'boolean',
        'selling_price' => 'decimal:2',
        'click_charge' => 'decimal:2',
        'base_hpp_snapshot' => 'decimal:2',
        'vendor_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'cut_length_m' => 'decimal:2',
        'roll_width_snapshot' => 'decimal:2',
        'roll_area_used_m2' => 'decimal:2',
        'offcut_area_m2' => 'decimal:2',
        'offcut_width_cm' => 'integer',
        'qty_ordered' => 'integer',
        'printed_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    public function materialRoll(): BelongsTo
    {
        return $this->belongsTo(MaterialRoll::class, 'material_roll_id');
    }

    public function printerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Get snapshot item name or fallback to material name
     */
    public function getItemNameAttribute(): string
    {
        if (!empty($this->product_name_snapshot)) {
            return $this->product_name_snapshot;
        }
        return $this->material->material_name ?? ($this->dimension_text ?: 'Bahan Cetak');
    }
}
