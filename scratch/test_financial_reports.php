<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$owner = User::where('role', 'owner')->first();
Auth::login($owner);

$controllers = [
    'Owner Dashboard' => fn() => (new \App\Http\Controllers\OwnerController())->dashboard(new Request()),
    'Profit & Loss' => fn() => (new \App\Http\Controllers\Report\ProfitLossController())->index(new Request()),
    'Finance Dashboard' => fn() => (new \App\Http\Controllers\FinanceDashboardController())->index(new Request()),
    'Cash Out Report' => fn() => (new \App\Http\Controllers\Report\CashOutReportController())->index(new Request()),
    'Expense Report' => fn() => (new \App\Http\Controllers\Report\ExpenseReportController())->index(new Request()),
    'Cash Mutation' => fn() => (new \App\Http\Controllers\Report\CashMutationController())->index(new Request()),
    'Cash Balance' => fn() => (new \App\Http\Controllers\Report\CashBalanceController())->index(new Request()),
    'Sales Report' => fn() => (new \App\Http\Controllers\Report\SalesReportController())->index(new Request()),
];

foreach ($controllers as $name => $fn) {
    try {
        $view = $fn();
        $rendered = $view->render();
        echo "✅ {$name} rendered successfully (" . strlen($rendered) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "❌ {$name} failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
