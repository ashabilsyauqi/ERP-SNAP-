<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'supplier_id', 'material_name', 'category', 'fixed_size', 'purchase_price', 'has_click_charge', 'click_charge', 'stock_qty', 'retail_price'])]
class Material extends Model
{
    use SoftDeletes;

    protected $casts = [
        'has_click_charge' => 'boolean',
        'click_charge' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
    ];

    /**
     * Get Total Base Unit HPP (Bahan Pokok + Biaya Klik Mesin)
     */
    public function getTotalHppAttribute(): float
    {
        $base = (float) $this->purchase_price;
        $click = ($this->has_click_charge || (float)$this->click_charge > 0) ? (float) $this->click_charge : 0;
        return $base + $click;
    }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function wholesalePrices(): HasMany
    {
        return $this->hasMany(MaterialWholesalePrice::class)->orderBy('min_qty', 'asc');
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
