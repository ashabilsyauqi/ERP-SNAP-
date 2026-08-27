<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;

class FixAccountingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-accounting-data 
                            {--branch= : ID Cabang tertentu (opsional, jika kosong akan memproses semua cabang)}
                            {--clean-orphan-sales : Hapus kas masuk mandiri akun 4-1000 yang tidak tertaut ke invoice POS}
                            {--force : Jalankan tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memperbaiki anomali HPP pada buku kas keluar dan menyelaraskan pendapatan POS dengan Laporan Laba Rugi.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branchId = $this->option('branch');
        $cleanOrphanSales = $this->option('clean-orphan-sales');
        $force = $this->option('force');

        $this->info("=== PERBAIKAN ANOMALI HPP & KONSISTENSI JURNAL KAS ===");
        
        $branchesQuery = Branch::query();
        if ($branchId && $branchId !== 'all') {
            $branchesQuery->where('id', $branchId);
        }
        $branches = $branchesQuery->get();

        if ($branches->isEmpty()) {
            $this->error("Cabang tidak ditemukan.");
            return 1;
        }

        $hppAccount = Account::where('kode_akun', '6-1000')->first();
        $hppAccountId = $hppAccount ? $hppAccount->id : null;

        // 1. Cari transaksi kas keluar HPP yang tidak valid
        $hppCashQuery = CashTransaction::where('tipe', 'keluar')
            ->where(function($q) use ($hppAccountId) {
                if ($hppAccountId) {
                    $q->where('account_id', $hppAccountId);
                }
                $q->orWhere('keterangan', 'like', '%Harga Pokok Penjualan (HPP)%');
            });

        if ($branchId && $branchId !== 'all') {
            $hppCashQuery->where('branch_id', $branchId);
        }

        $hppCount = $hppCashQuery->count();
        $hppAmount = $hppCashQuery->sum('jumlah');

        $this->info("Ditemukan {$hppCount} mutasi kas keluar HPP senilai Rp " . number_format($hppAmount, 0, ',', '.') . " yang salah tercatat sebagai pengeluaran kas fisik.");

        // 2. Cari orphan sales cash masuk jika diminta
        $orphanSalesQuery = CashTransaction::where('tipe', 'masuk')
            ->whereNull('transaction_id')
            ->whereHas('account', function($q) {
                $q->where('kode_akun', '4-1000');
            });

        if ($branchId && $branchId !== 'all') {
            $orphanSalesQuery->where('branch_id', $branchId);
        }

        $orphanCount = $orphanSalesQuery->count();
        $orphanAmount = $orphanSalesQuery->sum('jumlah');

        if ($orphanCount > 0) {
            $this->warn("Ditemukan {$orphanCount} transaksi kas masuk 'Pendapatan Penjualan' tanpa invoice POS senilai Rp " . number_format($orphanAmount, 0, ',', '.'));
        }

        if (!$force && !$this->confirm("Apakah Anda ingin menghapus mutasi HPP salah tersebut dan memulihkan saldo kas sebenarnya?", true)) {
            $this->warn("Dibatalkan.");
            return 0;
        }

        DB::beginTransaction();
        try {
            // Hapus mutasi HPP dari kas keluar
            $deletedHpp = $hppCashQuery->delete();

            $deletedOrphans = 0;
            if ($cleanOrphanSales && $orphanCount > 0) {
                $deletedOrphans = $orphanSalesQuery->delete();
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ Berhasil memperbaiki data jurnal kas dan laporan keuangan!");
            $this->table(
                ['Kategori Perbaikan', 'Jumlah Transaksi', 'Total Nominal (Rp)'],
                [
                    ['Penghapusan Fake Cash-Out HPP (6-1000)', $deletedHpp, 'Rp ' . number_format($hppAmount, 0, ',', '.')],
                    ['Penghapusan Duplikat Kas Masuk Penjualan (4-1000)', $deletedOrphans, 'Rp ' . number_format($cleanOrphanSales ? $orphanAmount : 0, 0, ',', '.')],
                ]
            );

            // Tampilkan Ringkasan Finansial Bersih Tiap Cabang
            $this->newLine();
            $this->info("=== RINGKASAN DATA KEUANGAN TERBARU ===");
            $summaryData = [];

            foreach ($branches as $b) {
                $sales = Transaction::where('branch_id', $b->id)->sum('total_price');
                $hpp = Transaction::where('branch_id', $b->id)->sum('total_hpp');
                $gross = $sales - $hpp;
                $cashIn = CashTransaction::where('branch_id', $b->id)->where('tipe', 'masuk')->sum('jumlah');
                $cashOut = CashTransaction::where('branch_id', $b->id)->where('tipe', 'keluar')->sum('jumlah');
                $cashBalance = $cashIn - $cashOut;

                $summaryData[] = [
                    $b->nama_cabang,
                    'Rp ' . number_format($sales, 0, ',', '.'),
                    'Rp ' . number_format($hpp, 0, ',', '.'),
                    'Rp ' . number_format($gross, 0, ',', '.'),
                    'Rp ' . number_format($cashIn, 0, ',', '.'),
                    'Rp ' . number_format($cashOut, 0, ',', '.'),
                    'Rp ' . number_format($cashBalance, 0, ',', '.'),
                ];
            }

            $this->table(
                ['Cabang', 'Omset POS', 'HPP Modal', 'Laba Kotor', 'Kas Masuk', 'Kas Keluar Ops', 'Saldo Kas Real'],
                $summaryData
            );

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Gagal melakukan perbaikan: " . $e->getMessage());
            return 1;
        }
    }
}
