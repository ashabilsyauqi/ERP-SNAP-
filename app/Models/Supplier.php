<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'perusahaan',
        'kontak',
        'alamat',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
