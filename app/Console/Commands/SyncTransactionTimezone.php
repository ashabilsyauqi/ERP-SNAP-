<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncTransactionTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-transaction-timezone 
                            {--date=today : Tanggal transaksi yang akan disinkronkan (today / YYYY-MM-DD)}
                            {--all : Sinkronkan seluruh transaksi lawas sebelum update timezone WIB}
                            {--hours=7 : Jumlah jam yang ditambahkan (default 7 jam untuk UTC ke WIB)}
                            {--max-hour=9 : Jam maksimal created_at yang dianggap UTC (default 9 untuk jam 00:00-09:59)}
                            {--cutoff=2026-09-05 16:00:00 : Waktu batas penarikan update WIB}
                            {--dry-run : Uji coba tanpa menyimpan perubahan ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyesuaikan created_at transaksi yang tercatat dalam UTC (+0) agar sesuai dengan jam operasional riil WIB (+7).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateOpt = $this->option('date');
        $allOpt = $this->option('all');
        $hours = (int) $this->option('hours');
        $maxHour = (int) $this->option('max-hour');
        $cutoff = $this->option('cutoff');
        $dryRun = $this->option('dry-run');

        $this->info("=== SINKRONISASI TIMESTAMP TRANSAKSI (UTC -> WIB) ===");
        if ($dryRun) {
            $this->warn("[DRY RUN] Mode simulasi aktif - tidak ada data yang diubah di database.");
        }

        $query = Transaction::query();

        if ($allOpt) {
            $query->where('created_at', '<', $cutoff);
        } else {
            $targetDate = $dateOpt === 'today' ? Carbon::today()->toDateString() : $dateOpt;
            $query->whereDate('created_at', $targetDate)
                  ->where('created_at', '<', $cutoff)
                  ->whereRaw("HOUR(created_at) <= {$maxHour}");
        }

        $transactions = $query->get();
        $total = $transactions->count();

        if ($total === 0) {
            $this->info("Tidak ditemukan transaksi yang perlu disinkronkan.");
            return 0;
        }

        $this->info("Ditemukan {$total} transaksi yang perlu dimajukan {$hours} jam ke WIB.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updatedCount = 0;

        foreach ($transactions as $trx) {
            $oldCreatedAt = $trx->created_at ? $trx->created_at->copy() : null;
            $oldUpdatedAt = $trx->updated_at ? $trx->updated_at->copy() : null;

            if ($oldCreatedAt) {
                $newCreatedAt = $oldCreatedAt->copy()->addHours($hours);
                $newUpdatedAt = $oldUpdatedAt ? $oldUpdatedAt->copy()->addHours($hours) : $newCreatedAt;

                if (!$dryRun) {
                    DB::table('transactions')
                        ->where('id', $trx->id)
                        ->update([
                            'created_at' => $newCreatedAt,
                            'updated_at' => $newUpdatedAt,
                        ]);

                    DB::table('transaction_details')
                        ->where('transaction_id', $trx->id)
                        ->update([
                            'created_at' => $newCreatedAt,
                            'updated_at' => $newUpdatedAt,
                        ]);

                    DB::table('cash_transactions')
                        ->where('transaction_id', $trx->id)
                        ->update([
                            'created_at' => $newCreatedAt,
                            'updated_at' => $newUpdatedAt,
                            'tanggal' => $newCreatedAt->toDateString(),
                        ]);
                }

                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Berhasil menyinkronkan {$updatedCount} transaksi ke zona waktu WIB (+{$hours} jam).");
        return 0;
    }
}
