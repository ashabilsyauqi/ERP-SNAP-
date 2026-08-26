<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CashTransaction;
use App\Models\Purchase;
use App\Models\PurchasePlan;
use App\Models\CashierShift;

echo "=== BRANCHES ===\n";
foreach (Branch::all() as $b) {
    echo "ID: {$b->id}, Name: {$b->nama_cabang}\n";
}

echo "\n=== TRANSACTIONS PER BRANCH ===\n";
foreach (Branch::all() as $b) {
    $txCount = Transaction::where('branch_id', $b->id)->count();
    $cashCount = CashTransaction::where('branch_id', $b->id)->count();
    $poCount = Purchase::where('branch_id', $b->id)->count();
    echo "Branch '{$b->nama_cabang}' (ID: {$b->id}): Transactions={$txCount}, CashTx={$cashCount}, Purchases={$poCount}\n";
}

echo "\n=== GLOBAL COUNTS ===\n";
echo "Total Transactions: " . Transaction::count() . "\n";
echo "Total Transaction Details: " . TransactionDetail::count() . "\n";
echo "Total Cash Transactions: " . CashTransaction::count() . "\n";
echo "Total Purchases: " . Purchase::count() . "\n";
echo "Total Purchase Plans: " . PurchasePlan::count() . "\n";
echo "Total Cashier Shifts: " . CashierShift::count() . "\n";
