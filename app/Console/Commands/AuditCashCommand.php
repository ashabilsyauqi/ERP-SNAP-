<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuditCashCommand extends Command
{
    protected $signature = 'app:audit-cash 
                            {--limit=15 : Jumlah data terakhir yang ditampilkan}
                            {--set-oct1= : ID transaksi yang ingin diubah tanggalnya ke 2026-10-01}
                            {--set-date= : Tanggal baru (YYYY-MM-DD atau DD/MM/YYYY) jika dipadukan dengan --id=}
                            {--id= : ID transaksi target}
                            {--normalize-all : Normalkan seluruh tanggal transaksi di database ke format YYYY-MM-DD}';

    protected $description = 'Audit dan periksa transaksi kas terbaru di database beserta tanggal dan nominalnya.';

    public function handle()
    {
        if ($this->option('normalize-all')) {
            $all = CashTransaction::all();
            $count = 0;
            foreach ($all as $trx) {
                $raw = $trx->getRawOriginal('tanggal');
                if ($raw) {
                    $clean = trim((string)$raw);
                    $formatted = null;
                    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $clean, $m)) {
                        $formatted = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
                    } elseif (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $clean, $m)) {
                        $formatted = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
                    }
                    if ($formatted && $raw !== $formatted) {
                        DB::table('cash_transactions')->where('id', $trx->id)->update(['tanggal' => $formatted]);
                        $count++;
                    }
                }
            }
            $this->info("BERHASIL: Menormalkan {$count} tanggal transaksi menjadi YYYY-MM-DD standar.");
            return 0;
        }

        if ($this->option('set-oct1')) {
            $id = $this->option('set-oct1');
            $trx = CashTransaction::find($id);
            if (!$trx) {
                $this->error("Transaksi dengan ID {$id} tidak ditemukan.");
                return 1;
            }
            $oldDate = $trx->tanggal;
            $trx->tanggal = '2026-10-01';
            $trx->save();
            $this->info("BERHASIL: Transaksi #{$trx->nomor_referensi} (ID: {$id}) berhasil diubah dari tanggal {$oldDate} ke 2026-10-01.");
            return 0;
        }

        if ($this->option('id') && $this->option('set-date')) {
            $id = $this->option('id');
            $newDate = $this->option('set-date');
            $trx = CashTransaction::find($id);
            if (!$trx) {
                $this->error("Transaksi dengan ID {$id} tidak ditemukan.");
                return 1;
            }
            $oldDate = $trx->tanggal;
            $trx->tanggal = $newDate;
            $trx->save();
            $this->info("BERHASIL: Transaksi #{$trx->nomor_referensi} (ID: {$id}) berhasil diubah dari tanggal {$oldDate} ke {$trx->tanggal}.");
            return 0;
        }

        $limit = (int) $this->option('limit');
        $this->info("=== {$limit} TRANSAKSI KAS TERAKHIR DI DATABASE ===");

        $transactions = CashTransaction::with('branch', 'account')
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();

        if ($transactions->isEmpty()) {
            $this->warn("Tabel cash_transactions masih kosong.");
            return 0;
        }

        $headers = ['ID', 'No. Referensi', 'Tipe', 'Tanggal Raw', 'Format Display', 'Jumlah (Rp)', 'Cabang', 'Keterangan', 'Dibuat Pada'];
        $rows = $transactions->map(function ($t) {
            $raw = $t->getRawOriginal('tanggal');
            return [
                $t->id,
                $t->nomor_referensi,
                strtoupper($t->tipe),
                $raw,
                Carbon::parse($t->tanggal)->format('d/m/Y'),
                number_format($t->jumlah, 0, ',', '.'),
                $t->branch->nama_cabang ?? 'Pusat (null)',
                \Illuminate\Support\Str::limit($t->keterangan, 25),
                $t->created_at->format('d/m/Y H:i:s'),
            ];
        });

        $this->table($headers, $rows);

        $todayTrx = CashTransaction::whereDate('created_at', Carbon::today())->count();
        $this->info("Total transaksi yang diinput hari ini (created_at = " . Carbon::today()->toDateString() . "): {$todayTrx} transaksi.");

        return 0;
    }
}
