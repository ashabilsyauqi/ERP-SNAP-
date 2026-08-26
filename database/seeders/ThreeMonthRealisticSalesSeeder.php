<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Account;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ThreeMonthRealisticSalesSeeder extends Seeder
{
    /**
     * Skema Penjualan 3 Bulan Terakhir:
     * - Rata-rata omzet harian 2jt - 5jt per cabang
     * - Konservatif bertumbuh +5% per bulannya
     * - Multi-cabang (Grand Wisata / Pusat, BTR Bekasi, Tambun)
     */
    public function run(): void
    {
        $branches = Branch::all();
        $accounts = Account::all();
        $users = User::all();

        $kasAkun = $accounts->firstWhere('kode_akun', '1-1000') ?? $accounts->first();
        $bankAkun = $accounts->firstWhere('kode_akun', '1-1200') ?? $accounts->first();

        $now = Carbon::create(2026, 8, 25, 14, 0, 0);

        // Dummy Customers Pool
        $customerNames = [
            'PT Sinar Abadi', 'CV Karya Mandiri', 'Budi Santoso', 'Anisa Rahmawati', 
            'Rizky Pratama', 'Siti Nurhaliza', 'Toko Berkah Advertising', 'Komunitas Motor Bekasi',
            'Futsal League Tambun', 'Dewi Lestari', 'Agus Setiawan', 'Mega Indah Salon',
            'Kedai Kopi Kenangan Kita', 'Hendra Gunawan', 'SMA 1 Tambun Selatan'
        ];

        // Base Daily Targets per Branch (2jt - 4.5jt baseline di Bulan 1 / Juni 2026)
        $branchBaseDaily = [
            'Cabang Grand Wisata (Pusat)' => 3200000,
            'Cabang BTR Bekasi'           => 2600000,
            'Cabang Tambun'                => 2200000,
        ];

        // 3 Bulan: Bulan 0 (Juni 2026), Bulan 1 (Juli 2026 +5%), Bulan 2 (Agustus 2026 +5%)
        $monthsCount = 3;

        foreach ($branches as $branch) {
            $baseDaily = $branchBaseDaily[$branch->nama_cabang] ?? 2500000;
            
            $cashier = $users->where('branch_id', $branch->id)->where('role', 'cashier')->first()
                       ?? $users->where('role', 'cashier')->first()
                       ?? $users->first();

            $materials = Material::where('branch_id', $branch->id)->where('retail_price', '>', 0)->get();
            if ($materials->isEmpty()) {
                $materials = Material::where('retail_price', '>', 0)->get();
            }

            for ($m = $monthsCount - 1; $m >= 0; $m--) {
                $monthDate = $now->copy()->subMonths($m);
                $daysInMonth = ($m === 0) ? $now->day : $monthDate->daysInMonth;
                $monthIndex = ($monthsCount - 1) - $m; // 0 = Juni, 1 = Juli, 2 = Agustus

                // Pertumbuhan konservatif +5% per bulan
                $growthFactor = pow(1.05, $monthIndex);
                $dailyTarget = $baseDaily * $growthFactor;

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $currentDate = $monthDate->copy()->day($d);

                    // Variasi acak omzet harian antara Rp 2.000.000 - Rp 5.000.000
                    $dailyNoise = rand(85, 125) / 100; // ±15-25%
                    $actualDayTarget = $dailyTarget * $dailyNoise;
                    
                    // Pastikan berada di range rata-rata 2jt - 5jt
                    $actualDayTarget = max(2000000, min(5200000, $actualDayTarget));

                    $daySalesAccumulated = 0;
                    $trxIndex = 1;

                    while ($daySalesAccumulated < $actualDayTarget) {
                        // Pilih 1 - 3 jenis produk percetakan secara acak
                        $selectedMaterials = $materials->random(min(rand(1, 3), $materials->count()));
                        $totalTrxPrice = 0;
                        $totalTrxHpp = 0;
                        $detailItems = [];

                        foreach ($selectedMaterials as $mat) {
                            $qty = rand(1, 8);
                            // Cek tier grosir
                            $price = $mat->retail_price;
                            $wholesale = $mat->wholesalePrices->where('min_qty', '<=', $qty)->sortByDesc('min_qty')->first();
                            if ($wholesale) {
                                $price = $wholesale->wholesale_price;
                            }

                            $subtotal = $qty * $price;
                            $totalTrxPrice += $subtotal;
                            $totalTrxHpp += ($qty * $mat->purchase_price);

                            $detailItems[] = [
                                'material_id' => $mat->id,
                                'qty_ordered' => $qty,
                                'selling_price' => $price,
                                'subtotal' => $subtotal
                            ];
                        }

                        if ($totalTrxPrice == 0) break;

                        // Waktu transaksi jam 08:30 - 21:00
                        $trxTime = $currentDate->copy()->setTime(rand(8, 20), rand(0, 59), rand(0, 59));

                        // Metode pembayaran proporsional (Cash 45%, Transfer 35%, QRIS 20%)
                        $randPay = rand(1, 100);
                        if ($randPay <= 45) {
                            $payMethod = 'Cash';
                            $targetAccount = $kasAkun;
                        } elseif ($randPay <= 80) {
                            $payMethod = 'Transfer';
                            $targetAccount = $bankAkun;
                        } else {
                            $payMethod = 'QRIS';
                            $targetAccount = $bankAkun;
                        }

                        // Buat record Transaksi Penjualan
                        $invCode = 'INV-' . $trxTime->format('ymd') . '-' . strtoupper(Str::random(4));
                        $clientName = $customerNames[array_rand($customerNames)];

                        $transaction = Transaction::create([
                            'invoice_number' => $invCode,
                            'user_id' => $cashier->id,
                            'branch_id' => $branch->id,
                            'customer_name' => $clientName,
                            'customer_phone' => '08' . rand(1111111111, 9999999999),
                            'total_price' => $totalTrxPrice,
                            'paid_amount' => $totalTrxPrice,
                            'remaining_amount' => 0,
                            'payment_status' => 'PAID',
                            'total_hpp' => $totalTrxHpp,
                            'payment_method' => $payMethod,
                            'created_at' => $trxTime,
                            'updated_at' => $trxTime,
                        ]);

                        foreach ($detailItems as $item) {
                            TransactionDetail::create([
                                'transaction_id' => $transaction->id,
                                'material_id' => $item['material_id'],
                                'qty_ordered' => $item['qty_ordered'],
                                'selling_price' => $item['selling_price'],
                                'created_at' => $trxTime,
                                'updated_at' => $trxTime,
                            ]);
                        }

                        // Jurnal Kas Masuk otomatis untuk setiap transaksi
                        CashTransaction::create([
                            'nomor_referensi' => 'KM-' . $trxTime->format('ymd') . '-' . strtoupper(Str::random(4)),
                            'tanggal' => $trxTime->toDateString(),
                            'tipe' => 'masuk',
                            'account_id' => $targetAccount->id,
                            'branch_id' => $branch->id,
                            'user_id' => $cashier->id,
                            'transaction_id' => $transaction->id,
                            'jumlah' => $totalTrxPrice,
                            'keterangan' => 'Penerimaan Penjualan Kasir POS #' . $transaction->invoice_number . ' (' . $clientName . ')',
                            'created_at' => $trxTime,
                            'updated_at' => $trxTime,
                        ]);

                        $daySalesAccumulated += $totalTrxPrice;
                        $trxIndex++;
                    }
                }
            }
        }
    }
}
