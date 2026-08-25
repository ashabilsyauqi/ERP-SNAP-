<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePlanItem extends Model
{
    protected $fillable = [
        'purchase_plan_id',
        'material_id',
        'material_name',
        'supplier_id',
        'supplier_name',
        'fixed_size',
        'qty',
        'estimated_unit_price',
        'subtotal',
        'retail_price',
        'wholesale_prices',
        'purchase_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'wholesale_prices' => 'array',
            'fixed_size' => 'decimal:2',
            'estimated_unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'retail_price' => 'decimal:2',
        ];
    }

    public function purchasePlan(): BelongsTo
    {
        return $this->belongsTo(PurchasePlan::class, 'purchase_plan_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
