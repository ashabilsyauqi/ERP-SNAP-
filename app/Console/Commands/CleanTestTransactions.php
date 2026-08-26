<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CashTransaction;
use App\Models\Purchase;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use Illuminate\Support\Facades\DB;

class CleanTestTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-test-data 
                            {--branch=1 : ID Cabang yang ingin dibersihkan (Default: 1 untuk Grand Wisata / Pusat)} 
                            {--all-branches : Bersihkan data uji coba di semua cabang}
                            {--from-date=2026-08-21 : Hapus transaksi yang dibuat pada atau setelah tanggal ini}
                            {--all-data : Hapus SEMUA transaksi pada cabang target tanpa filter tanggal}
                            {--force : Jalankan tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan data transaksi uji coba (Penjualan, Kas Masuk/Keluar, Pembelian) pada cabang tertentu atau semua cabang.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branchOption = $this->option('branch');
        $allBranches = $this->option('all-branches');
        $fromDate = $this->option('from-date');
        $allData = $this->option('all-data');
        $force = $this->option('force');

        $queryBranches = Branch::query();
        if (!$allBranches) {
            $queryBranches->where('id', $branchOption);
        }
        $branches = $queryBranches->get();

        if ($branches->isEmpty()) {
            $this->error("Cabang dengan ID {$branchOption} tidak ditemukan.");
            return 1;
        }

        $branchNames = $branches->pluck('nama_cabang')->implode(', ');
        $this->info("Target Cabang: {$branchNames}");
        $this->info($allData ? "Mode: Hapus SEMUA transaksi pada cabang target." : "Mode: Hapus transaksi uji coba dibuat >= {$fromDate}");

        if (!$force && !$this->confirm("Apakah Anda yakin ingin menghapus data transaksi uji coba pada cabang tersebut?", true)) {
            $this->warn("Operasi dibatalkan oleh pengguna.");
            return 0;
        }

        DB::beginTransaction();
        try {
            $deletedTx = 0;
            $deletedTxDetails = 0;
            $deletedCash = 0;
            $deletedPurchases = 0;
            $deletedPlans = 0;

            foreach ($branches as $branch) {
                // 1. Transactions & Details
                $txQuery = Transaction::where('branch_id', $branch->id);
                if (!$allData && $fromDate) {
                    $txQuery->where('created_at', '>=', "{$fromDate} 00:00:00");
                }
                $transactions = $txQuery->get();
                $txIds = $transactions->pluck('id')->toArray();

                if (!empty($txIds)) {
                    $deletedTxDetails += TransactionDetail::whereIn('transaction_id', $txIds)->delete();
                    $deletedTx += Transaction::whereIn('id', $txIds)->delete();
                }

                // 2. Cash Transactions
                $cashQuery = CashTransaction::where('branch_id', $branch->id);
                if (!$allData && $fromDate) {
                    $cashQuery->where('created_at', '>=', "{$fromDate} 00:00:00");
                }
                $deletedCash += $cashQuery->delete();

                // 3. Purchases
                $poQuery = Purchase::where('branch_id', $branch->id);
                if (!$allData && $fromDate) {
                    $poQuery->where('created_at', '>=', "{$fromDate} 00:00:00");
                }
                $deletedPurchases += $poQuery->delete();

                // 4. Purchase Plans
                $planQuery = PurchasePlan::where('branch_id', $branch->id);
                if (!$allData && $fromDate) {
                    $planQuery->where('created_at', '>=', "{$fromDate} 00:00:00");
                }
                $plans = $planQuery->get();
                $planIds = $plans->pluck('id')->toArray();
                if (!empty($planIds)) {
                    PurchasePlanItem::whereIn('purchase_plan_id', $planIds)->delete();
                    $deletedPlans += PurchasePlan::whereIn('id', $planIds)->delete();
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ Berhasil membersihkan data transaksi uji coba!");
            $this->table(
                ['Entitas Data', 'Jumlah Record Dihapus'],
                [
                    ['Detail Penjualan (Transaction Details)', $deletedTxDetails],
                    ['Faktur Penjualan POS (Transactions)', $deletedTx],
                    ['Buku Kas Masuk / Keluar (Cash Transactions)', $deletedCash],
                    ['Pesanan Pengadaan (Purchase Orders)', $deletedPurchases],
                    ['Rencana Pengadaan (Purchase Plans)', $deletedPlans],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Gagal menghapus data: " . $e->getMessage());
            return 1;
        }
    }
}
