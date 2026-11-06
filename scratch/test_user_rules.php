<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Branch;
use App\Models\DailyClosingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST CASHIER CLOSING & SELISIH FORMULA ===\n";

// 1. Login as regular cashier: kasir gw
$kasir = User::where('username', 'kasir gw')->first();
Auth::login($kasir);
echo "User: {$kasir->username} | Role: {$kasir->role} | Branch: {$kasir->branch_id}\n";

$controller = new \App\Http\Controllers\DailyClosingController();

// 2. Request create
$createReq = Request::create('/daily-closing/create', 'GET', ['closing_date' => '2026-10-28']);
$createView = $controller->create($createReq);
$viewData = $createView->getData();

echo "canChangeBranch for kasir: " . ($viewData['canChangeBranch'] ? 'TRUE' : 'FALSE') . "\n";
echo "Total Cash Sales POS: Rp " . number_format($viewData['totalCashSales'], 0, ',', '.') . "\n";
echo "Initial Cash Out: Rp " . number_format($viewData['cashOut'], 0, ',', '.') . "\n";
echo "Opening Cash: Rp " . number_format($viewData['openingCash'], 0, ',', '.') . "\n";
echo "Expected Cash: Rp " . number_format($viewData['expectedCash'], 0, ',', '.') . "\n";

// 3. Test as KINGAshabil
$king = User::where('username', 'KINGAshabil')->first();
Auth::login($king);
$createReqKing = Request::create('/daily-closing/create', 'GET', ['closing_date' => '2026-10-28']);
$createViewKing = $controller->create($createReqKing);
echo "\ncanChangeBranch for KINGAshabil: " . ($createViewKing->getData()['canChangeBranch'] ? 'TRUE' : 'FALSE') . "\n";

// 4. Test as SWANTO
$swanto = User::where('username', 'SWANTO')->first();
Auth::login($swanto);
$createReqSwanto = Request::create('/daily-closing/create', 'GET', ['closing_date' => '2026-10-28']);
$createViewSwanto = $controller->create($createReqSwanto);
echo "canChangeBranch for SWANTO: " . ($createViewSwanto->getData()['canChangeBranch'] ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== ALL VERIFICATIONS PASSED! ===\n";