<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'user_id',
    'month',
    'year',
    'period_label',
    'start_date',
    'end_date',
    'total_items_sold',
    'total_omzet',
    'total_material_cost',
    'gross_profit',
    'pdf_filename',
    'pdf_path',
    'notes'
])]
class ProductSalesArchive extends Model
{
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_items_sold' => 'decimal:2',
        'total_omzet' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'gross_profit' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
