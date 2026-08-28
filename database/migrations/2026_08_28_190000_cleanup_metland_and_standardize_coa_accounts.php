<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Branch;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CashTransaction;
use App\Models\Purchase;
use App\Models\PurchasePlan;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use App\Models\Account;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up Cabang Metland / Tambun and all its associated data
        $metlandBranches = Branch::withTrashed()
            ->where('nama_cabang', 'like', '%Metland%')
            ->orWhere('nama_cabang', 'like', '%Tambun%')
            ->orWhere('id', 3)
            ->get();

        $fallbackBranch = Branch::where('id', '!=', 3)
            ->where('nama_cabang', 'not like', '%Metland%')
            ->where('nama_cabang', 'not like', '%Tambun%')
            ->first();

        foreach ($metlandBranches as $branch) {
            $branchId = $branch->id;

            // Delete transactions and details
            $transactions = Transaction::where('branch_id', $branchId)->get();
            foreach ($transactions as $trx) {
                TransactionDetail::where('transaction_id', $trx->id)->delete();
                $trx->delete();
            }

            // Delete cash transactions
            CashTransaction::where('branch_id', $branchId)->delete();

            // Delete purchases and purchase plans
            Purchase::where('branch_id', $branchId)->delete();
            if (class_exists(PurchasePlan::class)) {
                PurchasePlan::where('branch_id', $branchId)->delete();
            }

            // Delete materials and wholesale prices
            $materials = Material::where('branch_id', $branchId)->get();
            foreach ($materials as $mat) {
                MaterialWholesalePrice::where('material_id', $mat->id)->delete();
                $mat->forceDelete();
            }

            // Reassign any remaining users to fallback branch or branch 1
            if ($fallbackBranch) {
                User::where('branch_id', $branchId)->update(['branch_id' => $fallbackBranch->id]);
            }

            // Force delete the branch
            $branch->forceDelete();
        }

        // 2. Standardize COA: Rename 4-1000 to "Penambahan Cash"
        $accSales = Account::where('kode_akun', '4-1000')->first();
        if ($accSales) {
            $accSales->update(['nama_akun' => 'Penambahan Cash']);
        } else {
            Account::create([
                'kode_akun' => '4-1000',
                'nama_akun' => 'Penambahan Cash',
                'tipe'      => 'pendapatan',
                'is_active' => true,
            ]);
        }

        // 3. Remove redundant 5-1000 "Beban Bahan Baku" and reassign to 5-5000 or delete
        $accBebanBahan = Account::where('kode_akun', '5-1000')->first();
        $accOpex = Account::where('kode_akun', '5-5000')->first();

        if ($accBebanBahan) {
            if ($accOpex) {
                CashTransaction::where('account_id', $accBebanBahan->id)->update(['account_id' => $accOpex->id]);
            } else {
                CashTransaction::where('account_id', $accBebanBahan->id)->forceDelete();
            }
            $accBebanBahan->forceDelete();
        }

        // 4. Ensure 6-1000 "Harga Pokok Penjualan" exists
        $accHpp = Account::where('kode_akun', '6-1000')->first();
        if ($accHpp) {
            $accHpp->update(['nama_akun' => 'Harga Pokok Penjualan', 'tipe' => 'beban', 'is_active' => true]);
        } else {
            Account::create([
                'kode_akun' => '6-1000',
                'nama_akun' => 'Harga Pokok Penjualan',
                'tipe'      => 'beban',
                'is_active' => true,
            ]);
        }

        // 5. Update historical cash transaction descriptions from 'Pemasukan Penjualan POS' to 'Penambahan Cash Penjualan POS'
        \Illuminate\Support\Facades\DB::table('cash_transactions')
            ->where('keterangan', 'like', 'Pemasukan Penjualan POS%')
            ->update([
                'keterangan' => \Illuminate\Support\Facades\DB::raw("REPLACE(keterangan, 'Pemasukan Penjualan POS', 'Penambahan Cash Penjualan POS')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data cleanup
    }
};
