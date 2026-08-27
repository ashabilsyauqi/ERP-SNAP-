<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\CashTransaction;
use App\Models\Account;

echo "=== BRANCHES ===\n";
foreach (Branch::all() as $b) {
    echo "ID: {$b->id}, Name: {$b->nama_cabang}\n";
}

echo "\n=== TRANSACTIONS SUMMARY PER BRANCH ===\n";
foreach (Branch::all() as $b) {
    $sumSales = Transaction::where('branch_id', $b->id)->sum('total_price');
    $sumHpp = Transaction::where('branch_id', $b->id)->sum('total_hpp');
    $countTrx = Transaction::where('branch_id', $b->id)->count();
    echo "Branch '{$b->nama_cabang}' (ID {$b->id}): Count={$countTrx}, TotalSales=Rp " . number_format($sumSales) . ", TotalHpp=Rp " . number_format($sumHpp) . "\n";
}

echo "\n=== CASH TRANSACTIONS FOR BRANCH 4 (OR ZAMRUD) ===\n";
$zamrudBranch = Branch::where('nama_cabang', 'like', '%Zamrud%')->first();
if ($zamrudBranch) {
    echo "Zamrud Branch ID: {$zamrudBranch->id}\n";
    $cashIn = CashTransaction::with('account')->where('branch_id', $zamrudBranch->id)->where('tipe', 'masuk')->get();
    echo "Kas Masuk count: " . $cashIn->count() . ", Total: Rp " . number_format($cashIn->sum('jumlah')) . "\n";
    foreach ($cashIn as $c) {
        $acc = $c->account ? "{$c->account->kode_akun} - {$c->account->nama_akun}" : 'No Account';
        echo "  - [ID {$c->id}] Ref: {$c->nomor_referensi}, Date: {$c->tanggal}, Amt: Rp " . number_format($c->jumlah) . ", Acc: {$acc}, TrxID: " . ($c->transaction_id ?? 'NULL') . ", Ket: {$c->keterangan}\n";
    }

    $cashOut = CashTransaction::with('account')->where('branch_id', $zamrudBranch->id)->where('tipe', 'keluar')->get();
    echo "Kas Keluar count: " . $cashOut->count() . ", Total: Rp " . number_format($cashOut->sum('jumlah')) . "\n";
    foreach ($cashOut as $c) {
        $acc = $c->account ? "{$c->account->kode_akun} - {$c->account->nama_akun}" : 'No Account';
        echo "  - [ID {$c->id}] Ref: {$c->nomor_referensi}, Date: {$c->tanggal}, Amt: Rp " . number_format($c->jumlah) . ", Acc: {$acc}, TrxID: " . ($c->transaction_id ?? 'NULL') . ", Ket: {$c->keterangan}\n";
    }
}
