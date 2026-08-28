<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename 4-1000 to "Penjualan (POS)"
        $accSales = Account::where('kode_akun', '4-1000')->first();
        if ($accSales) {
            $accSales->update(['nama_akun' => 'Penjualan (POS)']);
        }

        // 2. Rename 4-2000 to "Penambahan Cash & Modal"
        $accOther = Account::where('kode_akun', '4-2000')->first();
        if ($accOther) {
            $accOther->update(['nama_akun' => 'Penambahan Cash & Modal']);
        }

        // 3. Update description of POS transactions in cash_transactions
        DB::table('cash_transactions')
            ->where('keterangan', 'like', 'Penambahan Cash Penjualan POS%')
            ->update([
                'keterangan' => DB::raw("REPLACE(keterangan, 'Penambahan Cash Penjualan POS', 'Penjualan POS')")
            ]);

        DB::table('cash_transactions')
            ->where('keterangan', 'like', 'Pemasukan Penjualan POS%')
            ->update([
                'keterangan' => DB::raw("REPLACE(keterangan, 'Pemasukan Penjualan POS', 'Penjualan POS')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
