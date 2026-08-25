<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Account;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ThreeYearRealisticSimulationSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $branches = Branch::all();
        $accounts = Account::all();
        $users = User::all();
        $suppliers = Supplier::all();

        $kasAkun = $accounts->firstWhere('kode_akun', '1-1000') ?? $accounts->first();
        $bankAkun = $accounts->firstWhere('kode_akun', '1-1200') ?? $accounts->first();

        $bebanBahan = $accounts->firstWhere('kode_akun', '5-1000') ?? $kasAkun;
        $bebanGaji = $accounts->firstWhere('kode_akun', '5-2000') ?? $kasAkun;
        $bebanSewa = $accounts->firstWhere('kode_akun', '5-3000') ?? $kasAkun;
        $bebanListrik = $accounts->firstWhere('kode_akun', '5-4000') ?? $kasAkun;
        $bebanOps = $accounts->firstWhere('kode_akun', '5-5000') ?? $kasAkun;

        $now = Carbon::create(2026, 8, 25, 14, 0, 0);

        $customerPool = [
            'PT Sinar Abadi Kreasi', 'CV Karya Mandiri Advertising', 'Budi Santoso (Event Organizer)', 
            'Anisa Rahmawati (Wedding Invitation)', 'Rizky Pratama (Sablon Kaos)', 'Siti Nurhaliza (Brand Hijab)', 
            'Toko Berkah Advertising', 'Komunitas Motor Yamaha Bekasi', 'Futsal League Tambun', 
            'Dewi Lestari (Notaris & PPAT)', 'Agus Setiawan (Spanduk Warung)', 'Mega Indah Salon & Spa', 
            'Kedai Kopi Kenangan Kita', 'Hendra Gunawan (Brosur Perumahan)', 'SMA 1 Tambun Selatan (OSIS)',
            'Universitas Islam 45 Bekasi', 'Klinik Medika Pratama', 'PT Grand Wisata Properti',
            'Resto Seafood 99', 'Bintang Sablon & Printing', 'Ahmad Dani (Biro Jasa STNK)'
        ];

        $branchConfig = [
            'Cabang Grand Wisata (Pusat)' => ['baseDaily' => 1400000, 'gaji' => 14500000, 'sewa' => 45000000, 'listrik' => 3200000, 'ops' => 1800000],
            'Cabang BTR Bekasi'           => ['baseDaily' => 1100000, 'gaji' => 11000000, 'sewa' => 35000000, 'listrik' => 2400000, 'ops' => 1400000],
            'Cabang Tambun'                => ['baseDaily' => 900000,  'gaji' => 9500000,  'sewa' => 30000000, 'listrik' => 2000000, 'ops' => 1200000],
        ];

        $totalMonths = 36; // 3 Tahun (36 Bulan)

        $transactionsBatch = [];
        $detailsBatch = [];
        $cashBatch = [];

        // Track sequential IDs for foreign keys in raw bulk insert
        $trxIdCounter = 1;

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

            for ($m = $totalMonths - 1; $m >= 0; $m--) {
                $monthDate = $now->copy()->subMonths($m);
                $daysInMonth = ($m === 0) ? $now->day : $monthDate->daysInMonth;
                $monthIndex = ($totalMonths - 1) - $m;

                $growthFactor = pow(1.028, $monthIndex);
                $currentDailyTarget = $cfg['baseDaily'] * $growthFactor;
                if ($m <= 6) {
                    $currentDailyTarget = max(2800000, $currentDailyTarget);
                }

                // Tiap hari buat 2 - 4 transaksi realistis (agar total omzet 2jt - 5jt/hari)
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $currentDate = $monthDate->copy()->day($d);
                    $isWeekend = ($currentDate->isSaturday() || $currentDate->isSunday());
                    $dayMultiplier = $isWeekend ? 1.25 : 1.0;
                    $actualDayTarget = $currentDailyTarget * (rand(85, 115) / 100) * $dayMultiplier;

                    $trxsInDay = rand(2, 4);
                    $pricePerTrx = $actualDayTarget / $trxsInDay;

                    for ($t = 0; $t < $trxsInDay; $t++) {
                        $trxTime = $currentDate->copy()->setTime(rand(8, 20), rand(0, 59), rand(0, 59))->toDateTimeString();
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

                        $selectedMat = $materials->random();
                        $qty = max(1, (int) round($pricePerTrx / max(1, $selectedMat->retail_price)));
                        $totalTrxPrice = (float) ($qty * $selectedMat->retail_price);
                        $totalTrxHpp = (float) ($qty * $selectedMat->purchase_price);

                        $invCode = 'INV-' . Carbon::parse($trxTime)->format('ymd') . '-' . strtoupper(Str::random(4));
                        $clientName = $customerPool[array_rand($customerPool)];

                        $currentTrxId = $trxIdCounter++;

                        $transactionsBatch[] = [
                            'id' => $currentTrxId,
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
                        ];

                        $detailsBatch[] = [
                            'transaction_id' => $currentTrxId,
                            'material_id' => $selectedMat->id,
                            'qty_ordered' => $qty,
                            'selling_price' => $selectedMat->retail_price,
                            'created_at' => $trxTime,
                            'updated_at' => $trxTime,
                        ];

                        $cashBatch[] = [
                            'nomor_referensi' => 'KM-' . Carbon::parse($trxTime)->format('ymd') . '-' . strtoupper(Str::random(4)),
                            'tanggal' => Carbon::parse($trxTime)->toDateString(),
                            'tipe' => 'masuk',
                            'account_id' => $targetAccount->id,
                            'branch_id' => $branch->id,
                            'user_id' => $cashier->id,
                            'transaction_id' => $currentTrxId,
                            'jumlah' => $totalTrxPrice,
                            'keterangan' => 'Penerimaan Penjualan Kasir POS #' . $invCode . ' (' . $clientName . ')',
                            'created_at' => $trxTime,
                            'updated_at' => $trxTime,
                        ];
                    }
                }

                // Pengeluaran Kas Bulanan
                if ($daysInMonth >= 10) {
                    $tgl = $monthDate->copy()->day(10)->setTime(10, 30, 0)->toDateTimeString();
                    $cashBatch[] = [
                        'nomor_referensi' => 'KK-' . Carbon::parse($tgl)->format('ymd') . '-PLN',
                        'tanggal' => Carbon::parse($tgl)->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanListrik->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'transaction_id' => null,
                        'jumlah' => $cfg['listrik'] * (rand(90, 115) / 100),
                        'keterangan' => 'Pembayaran Listrik PLN 3 Phase, PDAM & Internet ' . $monthDate->translatedFormat('F Y'),
                        'created_at' => $tgl,
                        'updated_at' => $tgl,
                    ];
                }

                if ($daysInMonth >= 15) {
                    $tgl = $monthDate->copy()->day(15)->setTime(14, 15, 0)->toDateTimeString();
                    $cashBatch[] = [
                        'nomor_referensi' => 'KK-' . Carbon::parse($tgl)->format('ymd') . '-OPS',
                        'tanggal' => Carbon::parse($tgl)->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanOps->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'transaction_id' => null,
                        'jumlah' => $cfg['ops'] * (rand(85, 120) / 100),
                        'keterangan' => 'Maintenance Mesin Cetak & Operasional Toko',
                        'created_at' => $tgl,
                        'updated_at' => $tgl,
                    ];
                }

                if ($daysInMonth >= 25) {
                    $tgl = $monthDate->copy()->day(25)->setTime(16, 0, 0)->toDateTimeString();
                    $cashBatch[] = [
                        'nomor_referensi' => 'KK-' . Carbon::parse($tgl)->format('ymd') . '-PAY',
                        'tanggal' => Carbon::parse($tgl)->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanGaji->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'transaction_id' => null,
                        'jumlah' => $cfg['gaji'],
                        'keterangan' => 'Payroll Gaji Staff, Kasir & Operator ' . $monthDate->translatedFormat('F Y'),
                        'created_at' => $tgl,
                        'updated_at' => $tgl,
                    ];
                }

                foreach ([8, 22] as $tglRestock) {
                    if ($daysInMonth >= $tglRestock) {
                        $tgl = $monthDate->copy()->day($tglRestock)->setTime(11, 0, 0)->toDateTimeString();
                        $cashBatch[] = [
                            'nomor_referensi' => 'KK-' . Carbon::parse($tgl)->format('ymd') . '-PO',
                            'tanggal' => Carbon::parse($tgl)->toDateString(),
                            'tipe' => 'keluar',
                            'account_id' => $bebanBahan->id,
                            'branch_id' => $branch->id,
                            'user_id' => $manager->id,
                            'transaction_id' => null,
                            'jumlah' => rand(4500000, 9500000),
                            'keterangan' => 'Pengadaan Bahan Baku Cetak Supplier',
                            'created_at' => $tgl,
                            'updated_at' => $tgl,
                        ];
                    }
                }

                if ($monthDate->month === 9 && $daysInMonth >= 5) {
                    $tgl = $monthDate->copy()->day(5)->setTime(9, 0, 0)->toDateTimeString();
                    $cashBatch[] = [
                        'nomor_referensi' => 'KK-' . Carbon::parse($tgl)->format('ymd') . '-SEWA',
                        'tanggal' => Carbon::parse($tgl)->toDateString(),
                        'tipe' => 'keluar',
                        'account_id' => $bebanSewa->id,
                        'branch_id' => $branch->id,
                        'user_id' => $manager->id,
                        'transaction_id' => null,
                        'jumlah' => $cfg['sewa'],
                        'keterangan' => 'Sewa Gedung Ruko 1 Tahun (' . $monthDate->year . ' - ' . ($monthDate->year + 1) . ')',
                        'created_at' => $tgl,
                        'updated_at' => $tgl,
                    ];
                }
            }
        }

        // Eksekusi Mass Batch Insert (Chunk 500 baris per query agar instan)
        foreach (array_chunk($transactionsBatch, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }
        foreach (array_chunk($detailsBatch, 500) as $chunk) {
            DB::table('transaction_details')->insert($chunk);
        }
        foreach (array_chunk($cashBatch, 500) as $chunk) {
            DB::table('cash_transactions')->insert($chunk);
        }
    }
}
