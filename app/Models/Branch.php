<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama_cabang', 'alamat', 'telepon'])]
class Branch extends Model
{
    use SoftDeletes;

    public function users() { return $this->hasMany(User::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }
    public function cashTransactions() { return $this->hasMany(CashTransaction::class); }
}
