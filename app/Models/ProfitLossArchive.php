<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitLossArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'period_type',
        'period_label',
        'start_date',
        'end_date',
        'total_omzet',
        'total_hpp',
        'gross_profit',
        'total_opex',
        'net_profit',
        'pdf_filename',
        'pdf_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_omzet' => 'decimal:2',
            'total_hpp' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'total_opex' => 'decimal:2',
            'net_profit' => 'decimal:2',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isSurplus(): bool
    {
        return (float) $this->net_profit >= 0;
    }

    public function getFormattedOmzetAttribute(): string
    {
        return 'Rp ' . number_format($this->total_omzet, 0, ',', '.');
    }

    public function getFormattedHppAttribute(): string
    {
        return 'Rp ' . number_format($this->total_hpp, 0, ',', '.');
    }

    public function getFormattedGrossProfitAttribute(): string
    {
        return 'Rp ' . number_format($this->gross_profit, 0, ',', '.');
    }

    public function getFormattedOpexAttribute(): string
    {
        return 'Rp ' . number_format($this->total_opex, 0, ',', '.');
    }

    public function getFormattedNetProfitAttribute(): string
    {
        return 'Rp ' . number_format($this->net_profit, 0, ',', '.');
    }
}
