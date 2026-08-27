<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
    ];

    /**
     * Get the branch the customer belongs to (or where registered).
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    /**
     * Get all transactions made by this customer.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Accessor: Total spent by customer.
     */
    public function getTotalSpentAttribute()
    {
        return $this->transactions()->sum('total_price');
    }

    /**
     * Accessor: Total transactions count.
     */
    public function getTotalOrdersAttribute()
    {
        return $this->transactions()->count();
    }

    /**
     * Accessor: Total outstanding receivables (DP/Piutang).
     */
    public function getTotalReceivablesAttribute()
    {
        return $this->transactions()->where('remaining_amount', '>', 0)->sum('remaining_amount');
    }
}
