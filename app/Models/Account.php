<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['kode_akun', 'nama_akun', 'tipe', 'parent_id', 'deskripsi', 'is_active'])]
class Account extends Model
{
    use SoftDeletes;

    public function parent() { return $this->belongsTo(Account::class, 'parent_id'); }
    public function children() { return $this->hasMany(Account::class, 'parent_id'); }
    public function cashTransactions() { return $this->hasMany(CashTransaction::class); }
    
    public function scopeActive($query) {
        return $query->where('is_active', true);
    }
}
