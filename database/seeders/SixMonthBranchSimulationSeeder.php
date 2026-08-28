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

class SixMonthBranchSimulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $accounts = Account::all();
        $users = User::all();
        $suppliers = Supplier::all();

        $salesAccount = $accounts->firstWhere('kode_akun', '4-1000'); // Penambahan Cash
        $hppAccount = $accounts->firstWhere('kode_akun', '6-1000');   // HPP (Harga Pokok Penjualan)

        // Expense Accounts Map
        $expenseAccounts = [
            '5-2000' => ['name' => 'Beban Gaji Karyawan', 'monthly_base' => 1200000],
            '5-3000' => ['name' => 'Beban Sewa Tempat', 'monthly_base' => 500000],
            '5-4000' => ['name' => 'Beban Listrik, Air & Internet', 'monthly_base' => 250000],
            '5-5000' => ['name' => 'Beban Operasional Toko', 'monthly_base' => 150000],
            '5-9000' => ['name' => 'Beban Pemeliharaan Mesin & Peralatan', 'monthly_base' => 100000],
        ];

        // Base starting revenue (Month 1 - March 2026) per branch
        // Average 2jt - 4jt / month with 5% monthly compounding growth
        $branchBaseRevenues = [
            'Cabang Grand Wisata (Pusat)' => 2800000,
            'Cabang BTR Bekasi'           => 2400000,
            'Cabang Tambun'                => 2100000,
        ];

        $now = Carbon::create(2026, 8, 23, 12, 0, 0);
        $monthsCount = 6;

        foreach ($branches as $branch) {
            $baseMonthlyTarget = $branchBaseRevenues[$branch->nama_cabang] ?? 2300000;
            
            $cashier = $users->where('branch_id', $branch->id)->where('role', 'cashier')->first()
                       ?? $users->where('role', 'cashier')->first()
                       ?? $users->first();

            $manager = $users->where('branch_id', $branch->id)->where('role', 'manager')->first()
                       ?? $users->where('role', 'manager')->first()
                       ?? $users->first();

            $purchaser = $users->where('branch_id', $branch->id)->where('role', 'purchasing')->first()
                         ?? $users->where('role', 'purchasing')->first()
                         ?? $users->first();

            $materials = Material::where('branch_id', $branch->id)->where('retail_price', '>', 0)->get();
            if ($materials->isEmpty()) {
                continue;
            }

            // Loop 6 months: Month index 0 (March 2026) to 5 (August 2026)
            for ($m = $monthsCount - 1; $m >= 0; $m--) {
                $monthDate = $now->copy()->subMonths($m);
                $daysInMonth = ($m === 0) ? $now->day : $monthDate->daysInMonth;
                $monthIndex = ($monthsCount - 1) - $m; // 0, 1, 2, 3, 4, 5

                // 5% compounding monthly growth + small realistic fluctuation (±2%)
                $growthFactor = pow(1.05, $monthIndex);
                $targetMonthlyRevenue = round($baseMonthlyTarget * $growthFactor * (1 + (rand(-20, 20) / 1000)));

                // Distribute revenue across 18 - 25 transactions in the month
                $numTrx = rand(18, 24);
                $currentMonthRev = 0;
                
                // Pre-generate target amounts per transaction summing close to target
                $avgTrx = $targetMonthlyRevenue / $numTrx;

                for ($t = 1; $t <= $numTrx; $t++) {
                    $day = rand(1, max(1, $daysInMonth));
                    $trxHour = rand(9, 20);
                    $trxMinute = rand(0, 59);
                    $trxDate = $monthDate->copy()->day($day)->hour($trxHour)->minute($trxMinute)->second(rand(0, 59));

                    // Target for this single transaction
                    $trxTarget = $avgTrx * (1 + (rand(-30, 30) / 100));
                    if ($t === $numTrx && ($targetMonthlyRevenue - $currentMonthRev) > 30000) {
                        $trxTarget = $targetMonthlyRevenue - $currentMonthRev;
                    }

                    // Build cart matching approximate trxTarget
                    $selectedMat = $materials->random();
                    $unitPrice = $selectedMat->retail_price;
                    $qty = max(1, (int) round($trxTarget / max(1, $unitPrice)));
                    
                    if ($selectedMat->material_name === 'Kertas A3+' || $selectedMat->material_name === 'Stiker Vinyl Glossy A3+') {
                        $qty = max(10, min(100, $qty));
                    } else {
                        $qty = max(1, min(5, $qty));
                    }

                    // Check wholesale pricing tier
                    $wp = $selectedMat->wholesalePrices()->where('min_qty', '<=', $qty)->orderBy('min_qty', 'desc')->first();
                    if ($wp) {
                        $unitPrice = $wp->wholesale_price;
                    }

                    $trxTotal = $qty * $unitPrice;
                    $trxHpp = $qty * $selectedMat->purchase_price;

                    $paymentMethods = ['Cash', 'Transfer', 'QRIS'];
                    $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                    // Create Transaction
                    $invoiceNum = 'INV-' . $trxDate->format('Ymd') . '-' . strtoupper(Str::random(4));
                    $transaction = Transaction::create([
                        'invoice_number' => $invoiceNum,
                        'user_id'        => $cashier->id,
                        'branch_id'      => $branch->id,
                        'total_price'    => $trxTotal,
                        'total_hpp'      => $trxHpp,
                        'payment_method' => $paymentMethod,
                        'created_at'     => $trxDate,
                        'updated_at'     => $trxDate,
                    ]);

                    // Create Detail
                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'material_id'    => $selectedMat->id,
                        'qty_ordered'    => $qty,
                        'selling_price'  => $unitPrice,
                        'created_at'     => $trxDate,
                        'updated_at'     => $trxDate,
                    ]);

                    // Cash Inflow
                    if ($salesAccount) {
                        CashTransaction::create([
                            'branch_id'       => $branch->id,
                            'account_id'      => $salesAccount->id,
                            'user_id'         => $cashier->id,
                            'tipe'            => 'masuk',
                            'nomor_referensi' => 'KM-' . $trxDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                            'tanggal'         => $trxDate->toDateString(),
                            'jumlah'          => $trxTotal,
                            'keterangan'      => 'Pemasukan POS invoice ' . $transaction->invoice_number,
                            'transaction_id'  => $transaction->id,
                            'created_at'      => $trxDate,
                            'updated_at'      => $trxDate,
                        ]);
                    }

                    // Cash Outflow HPP
                    if ($hppAccount) {
                        CashTransaction::create([
                            'branch_id'       => $branch->id,
                            'account_id'      => $hppAccount->id,
                            'user_id'         => $cashier->id,
                            'tipe'            => 'keluar',
                            'nomor_referensi' => 'KK-' . $trxDate->format('Ymd') . '-HPP-' . strtoupper(Str::random(3)),
                            'tanggal'         => $trxDate->toDateString(),
                            'jumlah'          => $trxHpp,
                            'keterangan'      => 'HPP dari invoice ' . $transaction->invoice_number,
                            'transaction_id'  => $transaction->id,
                            'created_at'      => $trxDate,
                            'updated_at'      => $trxDate,
                        ]);
                    }

                    $currentMonthRev += $trxTotal;
                }

                // 2. Monthly OPEX Expenses
                foreach ($expenseAccounts as $accCode => $accInfo) {
                    $acc = $accounts->firstWhere('kode_akun', $accCode);
                    if (!$acc) continue;

                    $expenseDay = match($accCode) {
                        '5-2000' => min(28, $daysInMonth), // Gaji akhir bulan
                        '5-3000' => min(1, $daysInMonth),  // Sewa awal bulan
                        '5-4000' => min(15, $daysInMonth), // Listrik/Air tengah bulan
                        default  => rand(5, min(25, $daysInMonth)),
                    };

                    $expenseDate = $monthDate->copy()->day($expenseDay)->hour(11)->minute(0)->second(0);
                    $expenseAmount = $accInfo['monthly_base'] + rand(-15000, 15000);

                    CashTransaction::create([
                        'branch_id'       => $branch->id,
                        'account_id'      => $acc->id,
                        'user_id'         => $manager->id,
                        'tipe'            => 'keluar',
                        'nomor_referensi' => 'KK-' . $expenseDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                        'tanggal'         => $expenseDate->toDateString(),
                        'jumlah'          => max(50000, $expenseAmount),
                        'keterangan'      => $accInfo['name'] . ' ' . $monthDate->translatedFormat('F Y'),
                        'transaction_id'  => null,
                        'created_at'      => $expenseDate,
                        'updated_at'      => $expenseDate,
                    ]);
                }

                // 3. Monthly Purchase Orders (PO / RFQ)
                $allMaterials = Material::where('branch_id', $branch->id)->get();
                $numPOs = rand(1, 2);
                for ($p = 1; $p <= $numPOs; $p++) {
                    $poMaterial = $allMaterials->random();
                    $poSupplier = $suppliers->firstWhere('id', $poMaterial->supplier_id) ?? $suppliers->random();
                    $poDay = rand(3, min(25, $daysInMonth));
                    $poDate = $monthDate->copy()->day($poDay)->hour(14)->minute(30);

                    $poQty = ($poMaterial->material_name === 'Kertas A3+' || $poMaterial->material_name === 'Stiker Vinyl Glossy A3+') ? rand(100, 300) : rand(10, 25);
                    $poTotal = $poQty * $poMaterial->purchase_price;

                    $poStatus = 'received';
                    $verifiedAt = $poDate->copy()->addDays(2);
                    $verifiedBy = $manager->id;
                    $notes = 'Barang telah diterima lengkap sesuai standar QC cabang ' . $branch->nama_cabang;

                    if ($m === 0 && $p === $numPOs) {
                        $poStatus = 'waiting_approval';
                        $verifiedAt = null;
                        $verifiedBy = null;
                        $notes = null;
                    }

                    Purchase::create([
                        'po_number'          => Purchase::generatePoNumber(),
                        'vendor_ref'         => 'INV-SUP-' . $poDate->format('Ymd') . '-' . rand(100, 999),
                        'branch_id'          => $branch->id,
                        'user_id'            => $purchaser->id,
                        'supplier_id'        => $poSupplier ? $poSupplier->id : null,
                        'material_id'        => $poMaterial->id,
                        'qty_bought'         => $poQty,
                        'total_cost'         => $poTotal,
                        'status'             => $poStatus,
                        'approved_by'        => ($poStatus !== 'waiting_approval') ? $manager->id : null,
                        'approved_at'        => ($poStatus !== 'waiting_approval') ? $poDate->copy()->addHours(3) : null,
                        'approval_notes'     => ($poStatus !== 'waiting_approval') ? 'Disetujui Manajer Cabang.' : null,
                        'verified_by'        => $verifiedBy,
                        'verified_at'        => $verifiedAt,
                        'verification_notes' => $notes,
                        'created_at'         => $poDate,
                        'updated_at'         => $verifiedAt ?? $poDate,
                    ]);
                }
            }
        }
    }
}
