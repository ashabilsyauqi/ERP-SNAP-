<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Account;
use App\Models\User;
use App\Models\Transaction;
use App\Models\CashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $accounts = Account::all();
        $users = User::all();

        $salesAccount = $accounts->firstWhere('kode_akun', '4-1000'); // Penambahan Cash
        $otherIncomeAccount = $accounts->firstWhere('kode_akun', '4-2000'); // Pendapatan Lain-lain
        
        $expenseAccounts = [
            '5-2000' => 3000000, // Beban Gaji
            '5-3000' => 2000000, // Beban Sewa
            '5-4000' => 800000,  // Beban Listrik & Air
            '5-5000' => 400000,  // Beban Operasional
            '5-9000' => 200000,  // Beban Lain-lain
        ];

        // 1. Seed Dummy POS Transactions and Link to Cash Inflow
        $cashier = $users->firstWhere('role', 'cashier') ?: $users->first();
        
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $numTransactions = rand(1, 4);

            for ($j = 1; $j <= $numTransactions; $j++) {
                $branch = $branches->random();
                $totalPrice = rand(100000, 1500000);
                $totalHpp = $totalPrice * 0.4; // assume 40% COGS

                // Create POS Transaction
                $trx = Transaction::create([
                    'branch_id' => $branch->id,
                    'invoice_number' => 'INV-' . $date->format('Ymd') . '-' . uniqid(),
                    'user_id' => $cashier->id,
                    'total_price' => $totalPrice,
                    'total_hpp' => $totalHpp,
                    'payment_method' => rand(0, 1) ? 'Cash' : 'Transfer',
                    'created_at' => $date,
                    'updated_at' => $date
                ]);

                // Link to Cash Inflow
                CashTransaction::create([
                    'branch_id' => $branch->id,
                    'account_id' => $salesAccount->id,
                    'user_id' => $cashier->id,
                    'tipe' => 'masuk',
                    'nomor_referensi' => 'KM-' . $date->format('Ymd') . '-' . uniqid(),
                    'tanggal' => $date,
                    'jumlah' => $totalPrice,
                    'keterangan' => 'Pemasukan POS dari invoice ' . $trx->invoice_number,
                    'transaction_id' => $trx->id,
                    'created_at' => $date,
                    'updated_at' => $date
                ]);

                // Link to HPP (outflow adjustment)
                $hppAccount = $accounts->firstWhere('kode_akun', '6-1000');
                if ($hppAccount) {
                    CashTransaction::create([
                        'branch_id' => $branch->id,
                        'account_id' => $hppAccount->id,
                        'user_id' => $cashier->id,
                        'tipe' => 'keluar',
                        'nomor_referensi' => 'KK-' . $date->format('Ymd') . '-HPP-' . uniqid(),
                        'tanggal' => $date,
                        'jumlah' => $totalHpp,
                        'keterangan' => 'Harga Pokok Penjualan (HPP) dari invoice ' . $trx->invoice_number,
                        'transaction_id' => $trx->id,
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);
                }
            }
        }

        // 2. Seed Dummy Cash Outflows (Expenses)
        $owner = $users->firstWhere('role', 'owner') ?: $users->first();
        
        foreach ($expenseAccounts as $code => $baseAmount) {
            $account = $accounts->firstWhere('kode_akun', $code);
            
            // Create 3-5 expenses per category over the last month
            for ($k = 0; $k < rand(2, 4); $k++) {
                $branch = $branches->random();
                $date = Carbon::now()->subDays(rand(1, 28));
                $amount = rand($baseAmount * 0.5, $baseAmount * 1.5);

                CashTransaction::create([
                    'branch_id' => $branch->id,
                    'account_id' => $account->id,
                    'user_id' => $owner->id,
                    'tipe' => 'keluar',
                    'nomor_referensi' => 'KK-' . $date->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'tanggal' => $date,
                    'jumlah' => $amount,
                    'keterangan' => 'Pembayaran ' . $account->nama_akun . ' periode ' . $date->format('F Y'),
                    'transaction_id' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
        }
    }
}
