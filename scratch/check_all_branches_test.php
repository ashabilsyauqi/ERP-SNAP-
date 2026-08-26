<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\CashTransaction;
use App\Models\Purchase;
use App\Models\Branch;

foreach (Branch::all() as $b) {
    $txCount = Transaction::where('branch_id', $b->id)->where('created_at', '>=', '2026-08-21 00:00:00')->count();
    $cashCount = CashTransaction::where('branch_id', $b->id)->where('created_at', '>=', '2026-08-21 00:00:00')->count();
    $poCount = Purchase::where('branch_id', $b->id)->where('created_at', '>=', '2026-08-21 00:00:00')->count();
    echo "Branch '{$b->nama_cabang}' (ID {$b->id}): Test Transactions={$txCount}, Test CashTx={$cashCount}, Test Purchases={$poCount}\n";
}
