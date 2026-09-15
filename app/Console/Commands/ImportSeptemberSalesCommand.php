<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\User;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportSeptemberSalesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:import-september {--force : Overwrite existing backdated imports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data rekap penjualan 01 & 02 September 2026 dari Accurate ke Snaprint ERP (1 transaksi per hari atas nama Ashabil)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('================================================================');
        $this->info('  IMPORT REKAP PENJUALAN 01 & 02 SEPTEMBER 2026 (ACCURATE)');
        $this->info('================================================================');

        $user = User::where('role', 'owner')->first() ?? User::first();
        if (!$user) {
            $this->error('User owner tidak ditemukan.');
            return 1;
        }

        $branch = Branch::first() ?? Branch::create([
            'nama_cabang' => 'Cabang Grand Wisata (Pusat)',
            'alamat' => 'Jl. Grand Wisata',
            'telepon' => '021-88005678',
        ]);

        $salesAccount = Account::where('kode_akun', '4-1000')->first() ?? Account::first();
        if (!$salesAccount) {
            $this->error('Akun Penjualan (4-1000) tidak ditemukan.');
            return 1;
        }

        $day1Items = [
            ['name' => 'AC 260 (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 152, 'total' => 273000],
            ['name' => 'AP 150 (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 35, 'total' => 75000],
            ['name' => 'Ap 150 A3+', 'unit' => 'lembar', 'qty' => 1, 'total' => 1000],
            ['name' => 'Bahan Albatros', 'unit' => 'METER', 'qty' => 4.1, 'total' => 205000],
            ['name' => 'CROMO (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 12, 'total' => 71000],
            ['name' => 'Cetak Lain - Lain', 'unit' => 'pcs', 'qty' => 1, 'total' => 64000],
            ['name' => 'Cutting A3', 'unit' => 'lembar', 'qty' => 4, 'total' => 16000],
            ['name' => 'Finishing Lain - Lain', 'unit' => 'pcs', 'qty' => 36, 'total' => 82500],
            ['name' => 'Flexy 280 (110)', 'unit' => 'METER', 'qty' => 11.48, 'total' => 218900],
            ['name' => 'Flexy 280 (220)', 'unit' => 'METER', 'qty' => 9.5, 'total' => 310000],
            ['name' => 'Flexy 280 (320)', 'unit' => 'METER', 'qty' => 3.65, 'total' => 197100],
            ['name' => 'Flexy 340 (210)', 'unit' => 'METER', 'qty' => 2.1, 'total' => 105000],
            ['name' => 'Flexy 340 (320)', 'unit' => 'METER', 'qty' => 4.25, 'total' => 293250],
            ['name' => 'Flexy 440 (220)', 'unit' => 'METER', 'qty' => 1.0, 'total' => 60000],
            ['name' => 'Kertas BW A3', 'unit' => 'lembar', 'qty' => 8, 'total' => 20000],
            ['name' => 'Kertas HVS A4 80 gram /500 lbr', 'unit' => 'lembar', 'qty' => 93, 'total' => 0],
            ['name' => 'Laminating Tipis Roll Glossy (1muka) A3 /319', 'unit' => 'lembar', 'qty' => 1, 'total' => 3000],
            ['name' => 'Paket Design', 'unit' => 'item', 'qty' => 1, 'total' => 35000],
            ['name' => 'Print BW A4 (Mesin IR)', 'unit' => 'lembar', 'qty' => 3, 'total' => 3000],
            ['name' => 'Print BW A4 (Mesin IR) Bb', 'unit' => 'lembar', 'qty' => 129, 'total' => 166200],
            ['name' => 'Print Color A3', 'unit' => 'lembar', 'qty' => 99, 'total' => 449000],
            ['name' => 'Print Color A3 Bb', 'unit' => 'lembar', 'qty' => 102, 'total' => 620000],
            ['name' => 'Print Color A4', 'unit' => 'lembar', 'qty' => 5, 'total' => 12500],
            ['name' => 'QUANTAC GLOSY (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 11, 'total' => 66000],
            ['name' => 'Sticker Ritrama Glossy (125)', 'unit' => 'METER', 'qty' => 5.22, 'total' => 339300],
        ];

        $day2Items = [
            ['name' => 'AC 260 (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 90, 'total' => 83000],
            ['name' => 'AP 150 (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 187, 'total' => 627000],
            ['name' => 'Ap 150 A3+', 'unit' => 'lembar', 'qty' => 236, 'total' => 11000],
            ['name' => 'Bahan Albatros', 'unit' => 'METER', 'qty' => 20.1, 'total' => 410000],
            ['name' => 'CROMO (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 40, 'total' => 120000],
            ['name' => 'Cutting A3', 'unit' => 'lembar', 'qty' => 16, 'total' => 64000],
            ['name' => 'Finishing Lain - Lain', 'unit' => 'pcs', 'qty' => 34, 'total' => 79000],
            ['name' => 'Flexy 280 (110)', 'unit' => 'METER', 'qty' => 14.6, 'total' => 284000],
            ['name' => 'Flexy 280 (220)', 'unit' => 'METER', 'qty' => 23.9, 'total' => 916000],
            ['name' => 'Flexy 280 (320)', 'unit' => 'METER', 'qty' => 5.0, 'total' => 300000],
            ['name' => 'Flexy 340 (110)', 'unit' => 'METER', 'qty' => 4.0, 'total' => 100000],
            ['name' => 'Flexy 340 (210)', 'unit' => 'METER', 'qty' => 1.0, 'total' => 50000],
            ['name' => 'Kertas BW A3', 'unit' => 'lembar', 'qty' => 2, 'total' => 15000],
            ['name' => 'Kertas HVS A3 80 gram /500 lbr', 'unit' => 'lembar', 'qty' => 59, 'total' => 29500],
            ['name' => 'Kertas HVS A4 80 gram /500 lbr', 'unit' => 'lembar', 'qty' => 24, 'total' => 0],
            ['name' => 'Laminating Tebal Folio/A4 /100 lbr', 'unit' => 'lembar', 'qty' => 5, 'total' => 25000],
            ['name' => 'Laminating Tipis Roll Doff (1muka) A3 /319', 'unit' => 'lembar', 'qty' => 2, 'total' => 6000],
            ['name' => 'Paket X-Banner Outdoor', 'unit' => 'set', 'qty' => 2, 'total' => 140000],
            ['name' => 'Print Color A3', 'unit' => 'lembar', 'qty' => 207, 'total' => 581000],
            ['name' => 'Print Color A3 Bb', 'unit' => 'lembar', 'qty' => 151, 'total' => 688000],
            ['name' => 'Print Color A4', 'unit' => 'lembar', 'qty' => 24, 'total' => 60000],
            ['name' => 'Print Color A4 BB', 'unit' => 'lembar', 'qty' => 42, 'total' => 126000],
            ['name' => 'Print Color Brosur Digital', 'unit' => 'rim/set', 'qty' => 2, 'total' => 230000],
            ['name' => 'Print Color Brosur Digital BB', 'unit' => 'rim/set', 'qty' => 2, 'total' => 870000],
            ['name' => 'Print Color Kn A3 (4 Klik) Bb', 'unit' => 'lembar', 'qty' => 17, 'total' => 500000],
            ['name' => 'QUANTAC GLOSY (32.5 X 48 cm)', 'unit' => 'lembar', 'qty' => 22, 'total' => 156000],
            ['name' => 'Ring Kawat No. 6 Putih /100 pcs', 'unit' => 'pcs', 'qty' => 5, 'total' => 32500],
            ['name' => 'STAMPEL', 'unit' => 'pcs', 'qty' => 1, 'total' => 100000],
            ['name' => 'Standing roll banner 60 X 160', 'unit' => 'set', 'qty' => 7, 'total' => 1680000],
            ['name' => 'Sticker Ritrama Glossy (125)', 'unit' => 'METER', 'qty' => 17.99, 'total' => 1549925],
        ];

        DB::beginTransaction();
        try {
            // Import Day 1
            $this->processDay(
                dateStr: '2026-09-01',
                invoiceNumber: 'INV-20260901-ACC',
                items: $day1Items,
                user: $user,
                branch: $branch,
                salesAccount: $salesAccount
            );

            // Import Day 2
            $this->processDay(
                dateStr: '2026-09-02',
                invoiceNumber: 'INV-20260902-ACC',
                items: $day2Items,
                user: $user,
                branch: $branch,
                salesAccount: $salesAccount
            );

            DB::commit();
            $this->info("\n[BERHASIL] Seluruh transaksi penjualan 01 & 02 September 2026 berhasil diimport!");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n[GAGAL] Terjadi kesalahan: " . $e->getMessage());
            return 1;
        }
    }

    protected function processDay(string $dateStr, string $invoiceNumber, array $items, $user, $branch, $salesAccount)
    {
        $this->line("\nMemproses Data Tanggal {$dateStr} ({$invoiceNumber})...");

        // Check if exists
        $existing = Transaction::where('invoice_number', $invoiceNumber)->first();
        if ($existing) {
            if ($this->option('force')) {
                $this->warn("Menghapus transaksi lama {$invoiceNumber} karena opsi --force aktif.");
                CashTransaction::where('transaction_id', $existing->id)->delete();
                TransactionDetail::where('transaction_id', $existing->id)->delete();
                $existing->delete();
            } else {
                $this->info("Transaksi {$invoiceNumber} sudah ada di database. Dilewati.");
                return;
            }
        }

        $timestamp = Carbon::parse($dateStr . ' 17:00:00');
        $totalNominal = array_sum(array_column($items, 'total'));
        $totalHpp = 0;

        $transaction = new Transaction([
            'branch_id' => $branch->id,
            'invoice_number' => $invoiceNumber,
            'user_id' => $user->id,
            'customer_name' => 'Ashabil (Owner)',
            'customer_phone' => '08123456789',
            'total_price' => $totalNominal,
            'original_price' => $totalNominal,
            'discount_amount' => 0,
            'total_hpp' => 0,
            'payment_method' => 'Transfer',
            'payment_status' => 'PAID',
            'paid_amount' => $totalNominal,
            'remaining_amount' => 0,
            'order_status' => 'completed',
            'production_notes' => 'Rekapitulasi Penjualan Harian Accurate ' . Carbon::parse($dateStr)->translatedFormat('d F Y'),
        ]);
        $transaction->timestamps = false;
        $transaction->created_at = $timestamp;
        $transaction->updated_at = $timestamp;
        $transaction->save();

        foreach ($items as $it) {
            $name = $it['name'];
            $unit = $it['unit'];
            $qty = $it['qty'];
            $itemTotal = (float) $it['total'];
            $isMeter = strtoupper($unit) === 'METER';

            // Find or associate closest Material in branch
            $material = Material::where('branch_id', $branch->id)
                ->where('material_name', $name)
                ->first();

            if (!$material) {
                $firstWord = explode(' ', trim($name))[0] ?? '';
                if ($firstWord) {
                    $material = Material::where('branch_id', $branch->id)
                        ->where('material_name', 'LIKE', '%' . $firstWord . '%')
                        ->first();
                }
            }

            // Estimate HPP (50% default if purchase price not set)
            $purchasePrice = $material ? (float)$material->purchase_price : ($itemTotal > 0 && $qty > 0 ? ($itemTotal / $qty) * 0.5 : 0);
            
            if (!$material) {
                $material = Material::create([
                    'branch_id' => $branch->id,
                    'material_name' => $name,
                    'category' => $isMeter ? 'Banner / Outdoor' : 'Digital Printing',
                    'purchase_price' => $purchasePrice,
                    'retail_price' => $isMeter ? $itemTotal : ($qty > 0 ? round($itemTotal / $qty, 2) : 0),
                    'stock_qty' => 1000,
                    'has_click_charge' => false,
                    'click_charge' => 0,
                ]);
            }

            $itemHpp = ($purchasePrice * $qty);
            $totalHpp += $itemHpp;

            $detail = new TransactionDetail([
                'transaction_id' => $transaction->id,
                'material_id' => $material->id,
                'qty_ordered' => $isMeter ? 1 : max(1, (int) round($qty)),
                'area_m2' => $isMeter ? $qty : null,
                'selling_price' => $isMeter ? $itemTotal : ($qty > 0 ? round($itemTotal / $qty, 2) : 0),
                'dimension_text' => $name . ($isMeter ? " ({$qty} meter)" : " ({$qty} {$unit})"),
                'click_charge' => 0,
            ]);
            $detail->timestamps = false;
            $detail->created_at = $timestamp;
            $detail->updated_at = $timestamp;
            $detail->save();
        }

        $transaction->total_hpp = $totalHpp;
        $transaction->save();

        // Create CashTransaction Inflow with guaranteed unique reference number
        $prefix = 'KM-' . str_replace('-', '', $dateStr) . '-';
        $num = 1;
        $nomorRef = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
        while (CashTransaction::where('nomor_referensi', $nomorRef)->exists()) {
            $num++;
            $nomorRef = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        $cashTrx = new CashTransaction([
            'branch_id' => $branch->id,
            'account_id' => $salesAccount->id,
            'user_id' => $user->id,
            'tipe' => 'masuk',
            'nomor_referensi' => $nomorRef,
            'tanggal' => $dateStr,
            'jumlah' => $totalNominal,
            'keterangan' => "Rekap Penjualan POS Accurate {$dateStr} (#{$invoiceNumber}) - Ashabil (Owner)",
            'transaction_id' => $transaction->id,
        ]);
        $cashTrx->timestamps = false;
        $cashTrx->created_at = $timestamp;
        $cashTrx->updated_at = $timestamp;
        $cashTrx->save();

        $this->info("✓ Berhasil: {$invoiceNumber} ({$nomorRef}) | Total Item: " . count($items) . " | Total Omzet: Rp " . number_format($totalNominal, 0, ',', '.'));
    }
}
