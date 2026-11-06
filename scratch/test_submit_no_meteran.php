<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Branch;
use App\Models\DailyClosingReport;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST FORM SUBMISSION WITHOUT METERAN/NOTES ===\n";

$kasir = User::where('username', 'kasir gw')->first();
Auth::login($kasir);

$controller = new \App\Http\Controllers\DailyClosingController();

$postData = [
    'branch_id' => $kasir->branch_id,
    'closing_date' => date('Y-m-d'),
    'shift_type' => 'Full Day',
    'actual_cash' => 390000,
    'total_cash_sales' => 386900,
    'total_transfer_sales' => 794500,
    'total_qris_sales' => 712500,
    'denominations' => [
        'bills' => [
            100000 => 3, // 300.000
            50000  => 1, // 50.000
            20000  => 2, // 40.000
        ],
        'coins' => []
    ],
    // No meteran, no notes, no opening cash, no non-pos cash in
];

$storeReq = Request::create('/daily-closing', 'POST', $postData);
$response = $controller->store($storeReq);

echo "Store response HTTP status: " . $response->getStatusCode() . "\n";
echo "Redirect URL: " . $response->headers->get('Location') . "\n";

$report = DailyClosingReport::where('branch_id', $kasir->branch_id)->orderBy('id', 'desc')->first();
echo "Created Report #{$report->report_number}\n";
echo "Actual Cash: Rp " . number_format($report->actual_cash, 0, ',', '.') . "\n";
echo "Expected Cash: Rp " . number_format($report->expected_cash, 0, ',', '.') . "\n";
echo "Cash Difference: Rp " . number_format($report->cash_difference, 0, ',', '.') . "\n";
echo "Shift: {$report->shift_type}\n";
echo "Meteran Start: " . var_export($report->click_counter_start, true) . "\n";
echo "Meteran End: " . var_export($report->click_counter_end, true) . "\n";
echo "Click Total: " . var_export($report->click_count_total, true) . "\n";

// Clean up test report
$report->forceDelete();
echo "Test report cleaned up.\n";
echo "=== SUCCESS! ===\n";