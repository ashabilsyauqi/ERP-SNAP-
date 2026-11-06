<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRoll extends Model
{
    protected $fillable = [
        'branch_id',
        'material_id',
        'roll_name',
        'roll_width_m',
        'current_length_m',
        'initial_length_m',
    ];

    protected $casts = [
        'roll_width_m' => 'decimal:2',
        'current_length_m' => 'decimal:2',
        'initial_length_m' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'material_roll_id');
    }

    /**
     * Check if this roll can fit a given dimension (Width x Length in meters),
     * and calculate the required cut length and leftover offcut width.
     *
     * Returns array with:
     * - possible: bool
     * - cut_length_m: float
     * - offcut_width_cm: int
     * - orientation: 'normal' | 'rotated'
     */
    public function evaluateFit(float $widthM, float $lengthM): ?array
    {
        $rollW = (float) $this->roll_width_m;
        $options = [];

        // Opsi 1: Normal (Lebar print masuk ke lebar roll)
        if ($rollW >= $widthM) {
            $options[] = [
                'orientation' => 'normal',
                'cut_length_m' => $lengthM,
                'offcut_width_cm' => (int) round(($rollW - $widthM) * 100),
                'label' => "Roll {$rollW}m (Potong {$lengthM}m)",
            ];
        }

        // Opsi 2: Diputar 90 derajat (Panjang print masuk ke lebar roll)
        if ($rollW >= $lengthM) {
            $options[] = [
                'orientation' => 'rotated',
                'cut_length_m' => $widthM,
                'offcut_width_cm' => (int) round(($rollW - $lengthM) * 100),
                'label' => "Roll {$rollW}m (Diputar 90° - Potong {$widthM}m)",
            ];
        }

        if (empty($options)) {
            return null; // Tidak muat di roll ini
        }

        // Urutkan opsi: prioritaskan yang waste (offcut) paling sedikit
        usort($options, function ($a, $b) {
            return $a['offcut_width_cm'] <=> $b['offcut_width_cm'];
        });

        $best = $options[0];
        $best['all_options'] = $options;
        $best['roll_id'] = $this->id;
        $best['roll_name'] = $this->roll_name;
        $best['roll_width_m'] = $rollW;
        $best['current_length_m'] = (float) $this->current_length_m;

        return $best;
    }
}
