<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DailyClosingReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'report_number',
        'branch_id',
        'user_id',
        'closing_date',
        'shift_type',
        'total_orders_count',
        'total_cash_sales',
        'total_transfer_sales',
        'total_qris_sales',
        'total_sales',
        'total_cash_in',
        'total_cash_out',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'click_counter_start',
        'click_counter_end',
        'click_count_total',
        'production_notes',
        'manager_signature_path',
        'manager_signed_at',
        'owner_id',
        'owner_signature_path',
        'owner_signed_at',
        'owner_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'manager_signed_at' => 'datetime',
            'owner_signed_at' => 'datetime',
            'total_cash_sales' => 'decimal:2',
            'total_transfer_sales' => 'decimal:2',
            'total_qris_sales' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_cash_in' => 'decimal:2',
            'total_cash_out' => 'decimal:2',
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
        ];
    }

    public static function generateReportNumber($branchName = 'PUSAT'): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branchName), 0, 3)) ?: 'PST';
        $dateStr = date('Ymd');
        $random = strtoupper(Str::random(3));
        return "CLO-{$code}-{$dateStr}-{$random}";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
