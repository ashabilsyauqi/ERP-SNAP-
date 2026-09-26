<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'material_id',
    'qty_ordered',
    'selling_price',
    'click_charge',
    'fixed_length_m',
    'custom_width_cm',
    'area_m2',
    'dimension_text',
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
        'vendor_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'qty_ordered' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
