<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyClosingWaste extends Model
{
    protected $fillable = [
        'daily_closing_report_id',
        'branch_id',
        'user_id',
        'material_id',
        'material_roll_id',
        'waste_qty',
        'unit',
        'hpp_per_unit',
        'total_hpp',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'waste_qty' => 'decimal:2',
            'hpp_per_unit' => 'decimal:2',
            'total_hpp' => 'decimal:2',
        ];
    }

    public function dailyClosingReport(): BelongsTo
    {
        return $this->belongsTo(DailyClosingReport::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function materialRoll(): BelongsTo
    {
        return $this->belongsTo(MaterialRoll::class);
    }
}