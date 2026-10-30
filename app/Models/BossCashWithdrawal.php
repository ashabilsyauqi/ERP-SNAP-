<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BossCashWithdrawal extends Model
{
    use HasFactory;

    protected $table = 'boss_cash_withdrawals';

    protected $fillable = [
        'branch_id',
        'user_id',
        'withdrawal_date',
        'amount',
        'taken_by',
        'purpose',
        'notes',
        'daily_closing_report_id',
    ];

    protected $casts = [
        'withdrawal_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dailyClosingReport()
    {
        return $this->belongsTo(DailyClosingReport::class, 'daily_closing_report_id');
    }
}
