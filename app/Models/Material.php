<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id', 
    'parent_material_id',
    'print_sides',
    'supplier_id', 
    'material_name', 
    'category', 
    'machine_tag', 
    'fixed_size', 
    'purchase_price', 
    'has_click_charge', 
    'click_charge', 
    'stock_qty', 
    'retail_price',
    'opname_draft_qty',
    'opname_draft_notes',
    'opname_draft_at',
    'opname_draft_by'
])]
class Material extends Model
{
    use SoftDeletes;

    protected $casts = [
        'parent_material_id' => 'integer',
        'print_sides' => 'integer',
        'has_click_charge' => 'boolean',
        'click_charge' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'stock_qty' => 'decimal:2',
        'opname_draft_qty' => 'decimal:2',
        'opname_draft_at' => 'datetime',
    ];

    /**
     * Parent material (if this material is a variant like 2 Sisi sharing stock with 1 Sisi)
     */
    public function parentMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'parent_material_id');
    }

    /**
     * Linked variants sharing physical stock with this master material
     */
    public function linkedVariants(): HasMany
    {
        return $this->hasMany(Material::class, 'parent_material_id');
    }

    /**
     * Get the primary material that holds master physical stock
     */
    public function getPrimaryMaterial(): Material
    {
        return ($this->parent_material_id && $this->parentMaterial) ? $this->parentMaterial : $this;
    }

    /**
     * Synchronize stock across all linked family members (parent and all children)
     */
    public function syncStockToLinked(float $newStock): void
    {
        $primaryId = $this->parent_material_id ?: $this->id;
        static::where('id', $primaryId)->orWhere('parent_material_id', $primaryId)->update(['stock_qty' => $newStock]);
        $this->stock_qty = $newStock;
    }

    /**
     * Get Total Base Unit HPP (Bahan Pokok + Biaya Klik Mesin)
     */
    public function getTotalHppAttribute(): float
    {
        $base = (float) $this->purchase_price;
        $multiplier = ($this->print_sides == 2 || $this->parent_material_id) ? 2 : 1;
        $click = ($this->has_click_charge || (float)$this->click_charge > 0) ? (float) $this->click_charge : 0;
        return $base + ($click * $multiplier);
    }

    /**
     * Display Unit (lbr, box, rim, buku, m², pcs)
     */
    public function getUnitAttribute(): string
    {
        if ($this->fixed_size) {
            return 'm²';
        }
        $cat = (string)$this->category;
        if (in_array($cat, ['Print Dokumen dan Sticker'])) {
            return 'lbr';
        }
        if (in_array($cat, ['Kartu Nama'])) {
            return 'box';
        }
        if (in_array($cat, ['Brosur'])) {
            return 'lbr';
        }
        if (in_array($cat, ['Nota', 'Buku Yasin'])) {
            return 'buku';
        }
        if (in_array($cat, ['Cetak Outdoor dan Indoor'])) {
            return 'm²';
        }
        return 'pcs';
    }

    public function getHasDraftOpnameAttribute(): bool
    {
        return !is_null($this->opname_draft_qty);
    }

    public function getDraftVarianceAttribute(): float
    {
        if (is_null($this->opname_draft_qty)) return 0;
        return round((float)$this->opname_draft_qty - (float)$this->stock_qty, 2);
    }

    public function draftOpnameUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opname_draft_by');
    }

    public function branch(): BelongsTo
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

    public function rolls(): HasMany
    {
        return $this->hasMany(MaterialRoll::class);
    }

    public function wholesalePrices(): HasMany
    {
        return $this->hasMany(MaterialWholesalePrice::class)->orderBy('min_qty', 'asc');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function opnameLogs(): HasMany
    {
        return $this->hasMany(StockOpnameLog::class)->orderBy('created_at', 'desc');
    }
}
