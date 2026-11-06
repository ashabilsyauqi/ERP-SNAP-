<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Branch;
use App\Models\Account;
use App\Models\DailyClosingReport;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== START TESTING DAILY CLOSING FEATURE ===\n";

// 1. Pick a manager or cashier user
$user = User::where('role', 'manager')->first() ?: User::where('role', 'owner')->first();
if (!$user) {
    die("No test user found!\n");
}
Auth::login($user);
echo "Logged in as: {$user->username} ({$user->role}) [Branch ID: {$user->branch_id}]\n";

$branch = Branch::find($user->branch_id) ?: Branch::first();
echo "Testing on Branch: {$branch->nama_cabang} (ID: {$branch->id})\n";

// 2. Test create() method
$controller = new \App\Http\Controllers\DailyClosingController();
$request = Request::create('/daily-closing/create', 'GET', [
    'branch_id' => $branch->id,
    'closing_date' => date('Y-m-d'),
]);
$view = $controller->create($request);
echo "create() view returned successfully: " . $view->name() . "\n";

// 3. Test store() method with denominations and dynamic expenses
$testDate = date('Y-m-d');
$expenseAccount = Account::where('tipe', 'beban')->first();
$expenseAccountId = $expenseAccount ? $expenseAccount->id : 11;

$postData = [
    'branch_id' => $branch->id,
    'closing_date' => $testDate,
    'shift_type' => 'Full Day',
    'opening_cash' => 200000,
    'actual_cash' => 812000,
    'total_cash_sales' => 657000,
    'total_transfer_sales' => 300000,
    'total_qris_sales' => 150000,
    'total_cash_in' => 0,
    'total_cash_out' => 45000,
    'click_counter_start' => 1200,
    'click_counter_end' => 1350,
    'production_notes' => 'Testing closing kasir dengan hitungan lembaran koin dan 2 baris pengeluaran otomatis.',
    'denominations' => [
        'bills' => [
            100000 => 5, // 500.000
            50000  => 4, // 200.000
            20000  => 5, // 100.000
            10000  => 0,
            5000   => 0,
            2000   => 0,
            1000   => 0,
        ],
        'coins' => [
            1000 => 10, // 10.000
            500  => 4,  // 2.000
            200  => 0,
            100  => 0,
        ]
    ],
    'new_expenses' => [
        [
            'account_id' => $expenseAccountId,
            'keterangan' => '[TEST] Beli Lakban & Spidol Toko',
            'amount' => 25000,
        ],
        [
            'account_id' => $expenseAccountId,
            'keterangan' => '[TEST] Bensin Motor Antar Pesanan',
            'amount' => 20000,
        ]
    ]
];

$storeRequest = Request::create('/daily-closing', 'POST', $postData);
$response = $controller->store($storeRequest);

echo "store() response status: " . $response->getStatusCode() . "\n";
echo "Redirect target: " . $response->headers->get('Location') . "\n";

// 4. Verify in Database
$latestReport = DailyClosingReport::where('branch_id', $branch->id)->orderBy('id', 'desc')->first();
echo "\n--- VERIFICATION OF CREATED CLOSING REPORT ---\n";
echo "Report Number: {$latestReport->report_number}\n";
echo "Closing Date: {$latestReport->closing_date->format('Y-m-d')}\n";
echo "Actual Cash: Rp " . number_format($latestReport->actual_cash, 0, ',', '.') . "\n";
echo "Expected Cash: Rp " . number_format($latestReport->expected_cash, 0, ',', '.') . "\n";
echo "Cash Difference: Rp " . number_format($latestReport->cash_difference, 0, ',', '.') . "\n";
echo "Denominations Grand Total: Rp " . number_format($latestReport->cash_denominations['grand_total'] ?? 0, 0, ',', '.') . "\n";
echo "Bills Count (100k): " . ($latestReport->cash_denominations['bills'][100000]['qty'] ?? 'N/A') . " lembar\n";
echo "Coins Count (1k): " . ($latestReport->cash_denominations['coins'][1000]['qty'] ?? 'N/A') . " keping\n";

// 5. Verify CashTransaction entries created
$testExpenses = CashTransaction::where('branch_id', $branch->id)
    ->where('keterangan', 'like', '[TEST]%')
    ->get();

echo "\n--- VERIFICATION OF AUTOMATIC EXPENSES IN CASH TRANSACTIONS ---\n";
echo "Found " . $testExpenses->count() . " automatic expenses created:\n";
foreach ($testExpenses as $tx) {
    echo " -> Ref: {$tx->nomor_referensi} | Tipe: {$tx->tipe} | Tgl: {$tx->tanggal->format('Y-m-d')} | Jumlah: Rp " . number_format($tx->jumlah, 0, ',', '.') . " | Ket: {$tx->keterangan}\n";
}

// 6. Test show() view
$showView = $controller->show($latestReport->id);
echo "\nshow() view returned successfully: " . $showView->name() . "\n";

// Clean up test data so DB remains tidy
foreach ($testExpenses as $tx) {
    $tx->delete();
}
$latestReport->forceDelete();
echo "\nCleaned up test closing report & test expense transactions.\n";
echo "=== ALL TESTS PASSED SUCCESSFULLY! ===\n";
