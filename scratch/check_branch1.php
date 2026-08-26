<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\CashTransaction;
use App\Models\Purchase;

echo "=== TRANSACTIONS FOR BRANCH 1 (GRAND WISATA / PUSAT) ===\n";
$txs = Transaction::where('branch_id', 1)->orderBy('id', 'desc')->take(20)->get();
foreach ($txs as $t) {
    echo "ID: {$t->id}, Inv: {$t->invoice_number}, Total: {$t->total_price}, Date: {$t->transaction_date}, Created: {$t->created_at}\n";
}

echo "\n=== RECENT CASH TRANSACTIONS FOR BRANCH 1 ===\n";
$cashTxs = CashTransaction::where('branch_id', 1)->orderBy('id', 'desc')->take(10)->get();
foreach ($cashTxs as $c) {
    echo "ID: {$c->id}, Code: {$c->transaction_code}, Type: {$c->type}, Amount: {$c->amount}, Date: {$c->transaction_date}, Created: {$c->created_at}\n";
}
