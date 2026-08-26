<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\CashTransaction;
use App\Models\Purchase;

echo "=== TRANSACTIONS CREATED AFTER 2026-08-20 IN BRANCH 1 ===\n";
$txs = Transaction::where('branch_id', 1)
    ->where('created_at', '>=', '2026-08-21 00:00:00')
    ->get();
echo "Found " . $txs->count() . " test sales transactions.\n";
foreach ($txs as $t) {
    echo "- ID: {$t->id}, Inv: {$t->invoice_number}, Total: {$t->total_price}, Created: {$t->created_at}\n";
}

echo "\n=== ALL CASH TRANSACTIONS CREATED AFTER 2026-08-20 IN BRANCH 1 ===\n";
$cash = CashTransaction::where('branch_id', 1)
    ->where('created_at', '>=', '2026-08-21 00:00:00')
    ->get();
echo "Found " . $cash->count() . " test cash transactions.\n";

echo "\n=== PURCHASES CREATED AFTER 2026-08-20 IN BRANCH 1 ===\n";
$purchases = Purchase::where('branch_id', 1)
    ->where('created_at', '>=', '2026-08-21 00:00:00')
    ->get();
echo "Found " . $purchases->count() . " test purchases.\n";
