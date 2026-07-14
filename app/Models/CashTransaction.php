<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Carbon\Carbon;

#[Fillable(['branch_id', 'account_id', 'user_id', 'tipe', 'nomor_referensi', 'tanggal', 'jumlah', 'keterangan', 'transaction_id'])]
class CashTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function account() { return $this->belongsTo(Account::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }

    public function scopeMasuk($query) {
        return $query->where('tipe', 'masuk');
    }

    public function scopeKeluar($query) {
        return $query->where('tipe', 'keluar');
    }

    public static function generateNomorReferensi($tipe)
    {
        $prefix = $tipe === 'masuk' ? 'KM-' : 'KK-';
        $date = Carbon::now()->format('Ymd');
        
        $latest = static::where('nomor_referensi', 'like', $prefix . $date . '-%')
                        ->orderBy('id', 'desc')
                        ->first();
                        
        if (!$latest) {
            $number = 1;
        } else {
            $parts = explode('-', $latest->nomor_referensi);
            $number = intval(end($parts)) + 1;
        }
        
        return $prefix . $date . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
