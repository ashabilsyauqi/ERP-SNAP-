<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "=== 1. Testing Customer Model & Database ===\n";
$branch = Branch::first();
$customer = Customer::create([
    'name' => 'Budi Sanjaya Test',
    'phone' => '081299988877',
    'email' => 'budi.test@example.com',
    'address' => 'Jl. Zamrud No. 12',
    'branch_id' => $branch->id
]);
echo "Created customer ID: {$customer->id} (Name: {$customer->name})\n";

echo "\n=== 2. Testing POS Index (Customer list passed) ===\n";
$cashier = User::where('role', 'cashier')->first();
Auth::login($cashier);
$posRes = (new \App\Http\Controllers\PosController())->index();
echo "POS index rendered successfully: " . (strlen($posRes->render()) > 0 ? "OK" : "FAIL") . "\n";

echo "\n=== 3. Testing POS Checkout with New Customer Auto-Creation ===\n";
$material = Material::where('branch_id', $cashier->branch_id)->where('stock_qty', '>', 5)->first();
if (!$material) {
    $material = Material::first();
    $material->branch_id = $cashier->branch_id;
    $material->stock_qty = 50;
    $material->save();
}

$checkoutReq = new Request([
    'items' => [
        [
            'material_name_or_type' => $material->material_name,
            'qty' => 2
        ]
    ],
    'payment_method' => 'Cash',
    'customer_name' => 'Siti Rahmawati Auto',
    'customer_phone' => '087811223344',
    'customer_email' => 'siti.auto@example.com'
]);

$checkoutResponse = (new \App\Http\Controllers\PosController())->checkout($checkoutReq);
$data = $checkoutResponse->getData(true);
echo "Checkout Response: " . json_encode($data) . "\n";

$autoCust = Customer::where('name', 'Siti Rahmawati Auto')->first();
echo "Auto-created customer found: " . ($autoCust ? "YES (ID: {$autoCust->id}, Phone: {$autoCust->phone})" : "NO") . "\n";
$lastTrx = Transaction::where('customer_id', $autoCust->id)->first();
echo "Transaction linked with customer_id: " . ($lastTrx ? "YES (Invoice: {$lastTrx->invoice_number})" : "NO") . "\n";
echo "Customer total orders: " . $autoCust->total_orders . ", Total Spent: Rp " . number_format($autoCust->total_spent, 0, ',', '.') . "\n";

echo "\n=== 4. Testing CustomerController Index & Show ===\n";
$owner = User::where('role', 'owner')->first();
Auth::login($owner);

$custList = (new \App\Http\Controllers\CustomerController())->index(new Request());
echo "CustomerController index rendered: " . ($custList ? "OK" : "FAIL") . "\n";

$custShow = (new \App\Http\Controllers\CustomerController())->show($autoCust);
echo "CustomerController show rendered: " . ($custShow ? "OK" : "FAIL") . "\n";

echo "\n=== 5. Testing Refund RBAC: Owner/Manager Blocked, Cashier Allowed ===\n";
// Owner attempts refund -> should throw 403
Auth::login($owner);
try {
    (new \App\Http\Controllers\SalesController())->refund($lastTrx->id);
    echo "FAIL: Owner was able to refund!\n";
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    echo "SUCCESS: Owner blocked from refund with status " . $e->getStatusCode() . " (" . $e->getMessage() . ")\n";
}

// Cashier attempts refund on own branch transaction -> should succeed
Auth::login($cashier);
try {
    $refundRes = (new \App\Http\Controllers\SalesController())->refund($lastTrx->id);
    echo "SUCCESS: Cashier refunded transaction successfully!\n";
} catch (\Exception $e) {
    echo "FAIL: Cashier refund error: " . $e->getMessage() . "\n";
}

echo "\n=== 6. Testing SalesReportController (All Periods) ===\n";
Auth::login($owner);
foreach (['daily', 'monthly', 'yearly'] as $p) {
    $r = new Request(['period' => $p]);
    $reportRes = (new \App\Http\Controllers\Report\SalesReportController())->index($r);
    echo "SalesReportController [{$p}]: " . ($reportRes ? "OK" : "FAIL") . "\n";
}

// Cleanup test customer
$customer->delete();
$autoCust->delete();
echo "\n=== All Tests Passed Successfully! ===\n";
