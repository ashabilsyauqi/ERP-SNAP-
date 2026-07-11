<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['material_id', 'min_qty', 'wholesale_price'])]
class MaterialWholesalePrice extends Model
{
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
