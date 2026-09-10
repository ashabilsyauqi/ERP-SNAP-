<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePlanPayment extends Model
{
    protected $fillable = [
        'purchase_plan_id',
        'branch_id',
        'user_id',
        'account_id',
        'cash_transaction_id',
        'payment_step',
        'payment_step_label',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function purchasePlan(): BelongsTo
    {
        return $this->belongsTo(PurchasePlan::class, 'purchase_plan_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function cashTransaction(): BelongsTo
    {
        return $this->belongsTo(CashTransaction::class, 'cash_transaction_id');
    }
}
