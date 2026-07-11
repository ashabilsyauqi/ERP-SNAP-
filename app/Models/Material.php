<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['material_name', 'fixed_size', 'purchase_price', 'stock_qty', 'retail_price'])]
class Material extends Model
{
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
}
