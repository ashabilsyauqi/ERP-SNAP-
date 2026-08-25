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
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ThreeYearRealisticSimulationSeeder extends Seeder
{
    /**
     * Skema Simulasi Bisnis Snaprint Berjalan 3 Tahun (36 Bulan):
     * Rentang: September 2023 s/d Agustus 2026 (36 Bulan)
     * - Multi-cabang: Grand Wisata (Pusat), BTR Bekasi, Tambun
     * - Penjualan Kasir POS harian (Rata-rata 2jt - 5jt per cabang) dengan pertumbuhan realistis naik +5% per bulan
     * - Pengeluaran Kas Rutin Lengkap per Cabang:
     *   1. Beban Gaji Karyawan (Tiap akhir bulan tgl 25-28)
     *   2. Beban Listrik, Air & Internet (Tiap awal bulan tgl 5-10)
     *   3. Beban Sewa Tempat (Tiap awal periode/tahunan/semester)
     *   4. Beban Operasional & Pemeliharaan Mesin Cetak (Tiap pertengahan bulan)
     *   5. Pembelian Bahan Baku / PO Supplier (Restock rutin per minggu)
     */
    public function run(): void
    {
        // Nonaktifkan foreign key / query log untuk performa batch insert cepat
        DB::disableQueryLog();

        $branches = Branch::all();
        $accounts = Account::all();
        $users = User::all();
        $suppliers = Supplier::all();

        $kasAkun = $accounts->firstWhere('kode_akun', '1-1000') ?? $accounts->first();
        $bankAkun = $accounts->firstWhere('kode_akun', '1-1200') ?? $accounts->first();

        // Akun Beban
        $bebanBahan = $accounts->firstWhere('kode_akun', '5-1000') ?? $kasAkun;
        $bebanGaji = $accounts->firstWhere('kode_akun', '5-2000') ?? $kasAkun;
        $bebanSewa = $accounts->firstWhere('kode_akun', '5-3000') ?? $kasAkun;
        $bebanListrik = $accounts->firstWhere('kode_akun', '5-4000') ?? $kasAkun;
        $bebanOps = $accounts->firstWhere('kode_akun', '5-5000') ?? $kasAkun;
        $bebanLain = $accounts->firstWhere('kode_akun', '5-9000') ?? $kasAkun;

        $now = Carbon::create(2026, 8, 25, 14, 0, 0);

        // Dummy Customer Pool
        $customerPool = [
            'PT Sinar Abadi Kreasi', 'CV Karya Mandiri Advertising', 'Budi Santoso (Event Organizer)', 
            'Anisa Rahmawati (Wedding Invitation)', 'Rizky Pratama (Sablon Kaos)', 'Siti Nurhaliza (Brand Hijab)', 
            'Toko Berkah Advertising', 'Komunitas Motor Yamaha Bekasi', 'Futsal League Tambun', 
            'Dewi Lestari (Notaris & PPAT)', 'Agus Setiawan (Spanduk Warung)', 'Mega Indah Salon & Spa', 
            'Kedai Kopi Kenangan Kita', 'Hendra Gunawan (Brosur Perumahan)', 'SMA 1 Tambun Selatan (OSIS)',
            'Universitas Islam 45 Bekasi', 'Klinik Medika Pratama', 'PT Grand Wisata Properti',
            'Resto Seafood 99', 'Bintang Sablon & Printing', 'Ahmad Dani (Biro Jasa STNK)'
        ];

        // Base daily revenue starting 3 years ago (Sept 2023)
        $branchConfig = [
            'Cabang Grand Wisata (Pusat)' => ['baseDaily' => 1400000, 'gaji' => 14500000, 'sewa' => 45000000, 'listrik' => 3200000, 'ops' => 1800000],
            'Cabang BTR Bekasi'           => ['baseDaily' => 1100000, 'gaji' => 11000000, 'sewa' => 35000000, 'listrik' => 2400000, 'ops' => 1400000],
            'Cabang Tambun'                => ['baseDaily' => 900000,  'gaji' => 9500000,  'sewa' => 30000000, 'listrik' => 2000000, 'ops' => 1200000],
        ];

        $totalMonths = 36; // 3 Tahun

        foreach ($branches as $branch) {
            $cfg = $branchConfig[$branch->nama_cabang] ?? [
                'baseDaily' => 1000000, 'gaji' => 10000000, 'sewa' => 30000000, 'listrik' => 2000000, 'ops' => 1200000
            ];

            $cashier = $users->where('branch_id', $branch->id)->where('role', 'cashier')->first()
                       ?? $users->where('role', 'cashier')->first()
                       ?? $users->first();

            $manager = $users->where('branch_id', $branch->id)->where('role', 'manager')->first()
                       ?? $users->where('role', 'manager')->first()
                       ?? $users->first();

            $materials = Material::where('branch_id', $branch->id)->where('retail_price', '>', 0)->get();
            if ($materials->isEmpty()) {
                $materials = Material::where('retail_price', '>', 0)->get();
            }

            // Loop 36 bulan dari masa lalu (Sept 2023) sampai sekarang (Agustus 2026)
            for ($m = $totalMonths - 1; $m >= 0; $m--) {
                $monthDate = $now->copy()->subMonths($m);
                $daysInMonth = ($m === 0) ? $now->day : $monthDate->daysInMonth;
                $monthIndex = ($totalMonths - 1) - $m; // 0 sampai 35

                // Kenaikan tren penjualan bertahap sehingga di tahun 2026 mencapai 2jt - 5jt / hari
                $growthFactor = pow(1.028, $monthIndex); // +2.8% compounded per bulan (~3x lipat dalam 3 tahun)
                $currentDailyTarget = $cfg['baseDaily'] * $growthFactor;

                // Pastikan pada 3-6 bulan terakhir berada di rata-rata 2.5jt - 5jt
                if ($m <= 6) {
                    $currentDailyTarget = max(2800000, $currentDailyTarget);
                }

                // =========================================================
                // 1. TRANSAKSI PENJUALAN KASIR POS HARIAN (Setiap Hari)
                // =========================================================
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $currentDate = $monthDate->copy()->day($d);

                    // Weekend biasanya lebih ramai (faktor 1.25)
                    $isWeekend = ($currentDate->isSaturday() || $currentDate->isSunday());
                    $dayMultiplier = $isWeekend ? 1.25 : 1.0;
                    $dailyNoise = (rand(85, 115) / 100) * $dayMultiplier;
                    $actualDayTarget = $currentDailyTarget * $dailyNoise;

                    $daySalesAccumulated = 0;

                    while ($daySalesAccumulated < $actualDayTarget) {
                        $selectedMaterials = $materials->random(min(rand(1, 3), $materials->count()));
                        $totalTrxPrice = 0;
                        $totalTrxHpp = 0;
                        $detailItems = [];

                        foreach ($selectedMaterials as $mat) {
                            $qty = rand(1, 10);
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

                        $trxTime = $currentDate->copy()->setTime(rand(8, 20), rand(0, 59), rand(0, 59));
                        $randPay = rand(1, 100);
                        if ($randPay <= 45) {
                            $payMethod = 'Cash';
                            $targetAccount = $kasAkun;
                        } elseif ($randPay <= 75) {
                            $payMethod = 'Transfer';
                            $targetAccount = $bankAkun;
                        } else {
                            $payMethod = 'QRIS';
                            $targetAccount = $bankAkun;
                        }

                        $invCode = 'INV-' . $trxTime->format('ymd') . '-' . strtoupper(Str::random(4));
                        $clientName = $customerPool[array_rand($customerPool)];

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

                        // Jurnal Kas Masuk
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
                    }
                }

                // =========================================================
                // 2. PENGELUARAN KAS RUTIN BULANAN (Expenses & Disbursements)
                // =========================================================
                
                // A. Pembayaran Beban Listrik, Air & Internet (Tgl 10 tiap bulan)
                if ($daysInMonth >= 10) {
                    $tglListrik = $monthDate->copy()->day(10)->setTime(10, 30, 0);
                    $nominalListrik = $cfg['listrik'] * (rand(90, 115) / 100);
                    CashTransaction::create([
                        'nomor_referensi' => 'KK-' . $tglListrik->format('ymd') . '-PLN',
                        'tanggal' => $tglListrik->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanListrik->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'jumlah' => $nominalListrik,
                        'keterangan' => 'Pembayaran Listrik PLN 3 Phase, PDAM & Internet Dedicated ' . $monthDate->translatedFormat('F Y'),
                        'created_at' => $tglListrik,
                        'updated_at' => $tglListrik,
                    ]);
                }

                // B. Beban Operasional & Maintenance Mesin Cetak (Tgl 15 tiap bulan)
                if ($daysInMonth >= 15) {
                    $tglOps = $monthDate->copy()->day(15)->setTime(14, 15, 0);
                    $nominalOps = $cfg['ops'] * (rand(85, 120) / 100);
                    CashTransaction::create([
                        'nomor_referensi' => 'KK-' . $tglOps->format('ymd') . '-OPS',
                        'tanggal' => $tglOps->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanOps->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'jumlah' => $nominalOps,
                        'keterangan' => 'Maintenance Printhead, Pembersihan Mesin & Konsumsi Operasional Toko',
                        'created_at' => $tglOps,
                        'updated_at' => $tglOps,
                    ]);
                }

                // C. Beban Gaji Karyawan & Kasir (Tgl 25 tiap bulan)
                if ($daysInMonth >= 25) {
                    $tglGaji = $monthDate->copy()->day(25)->setTime(16, 0, 0);
                    $nominalGaji = $cfg['gaji'];
                    CashTransaction::create([
                        'nomor_referensi' => 'KK-' . $tglGaji->format('ymd') . '-PAY',
                        'tanggal' => $tglGaji->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanGaji->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'jumlah' => $nominalGaji,
                        'keterangan' => 'Payroll Gaji Pokok & Tunjangan Operator Cetak, Kasir & Staff ' . $monthDate->translatedFormat('F Y'),
                        'created_at' => $tglGaji,
                        'updated_at' => $tglGaji,
                    ]);
                }

                // D. Restock Bahan Baku Supplier (2x per bulan: Tgl 8 dan Tgl 22)
                foreach ([8, 22] as $tglRestock) {
                    if ($daysInMonth >= $tglRestock) {
                        $tglBahan = $monthDate->copy()->day($tglRestock)->setTime(11, 0, 0);
                        $nominalBahan = rand(4500000, 9500000);
                        $supplierName = $suppliers->isNotEmpty() ? $suppliers->random()->name : 'PT Bintang Terang';
                        
                        CashTransaction::create([
                            'nomor_referensi' => 'KK-' . $tglBahan->format('ymd') . '-PO',
                            'tanggal' => $tglBahan->toDateString(),
                            'tipe' => 'keluar',
                            'account_id' => $bebanBahan->id,
                            'branch_id' => $branch->id,
                            'user_id' => $manager->id,
                            'jumlah' => $nominalBahan,
                            'keterangan' => 'Pembayaran Pengadaan Bahan Baku Cetak (Flexi, Stiker, Tinta) ke ' . $supplierName,
                            'created_at' => $tglBahan,
                            'updated_at' => $tglBahan,
                        ]);
                    }
                }

                // E. Beban Sewa Ruko / Gedung (Tahunan setiap bulan September)
                if ($monthDate->month === 9 && $daysInMonth >= 5) {
                    $tglSewa = $monthDate->copy()->day(5)->setTime(09, 0, 0);
                    CashTransaction::create([
                        'nomor_referensi' => 'KK-' . $tglSewa->format('ymd') . '-SEWA',
                        'tanggal' => $tglSewa->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanSewa->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'jumlah' => $cfg['sewa'],
                        'keterangan' => 'Pembayaran Sewa Gedung / Ruko Cabang Periode 1 Tahun (' . $monthDate->year . ' - ' . ($monthDate->year + 1) . ')',
                        'created_at' => $tglSewa,
                        'updated_at' => $tglSewa,
                    ]);
                }
            }
        }
    }
}
