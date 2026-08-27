<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Material;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$cashier = User::where('role', 'cashier')->first();
Auth::login($cashier);

$material = Material::where('branch_id', $cashier->branch_id)
    ->where('material_name', 'like', '%flexy china 280%')
    ->first();

if (!$material) {
    $material = Material::where('branch_id', $cashier->branch_id)->first();
}

echo "Testing Material: {$material->material_name} (ID: {$material->id}, Retail Price: {$material->retail_price})\n";

// Simulation: 2.00m x 4.90m = 9.80 m²
$width = 2.00;
$length = 4.90;
$area = 9.80;
$expectedPrice = round($area * $material->retail_price); // 9.80 * 20000 = 196000

echo "Expected Price: Rp " . number_format($expectedPrice, 0, ',', '.') . "\n";

$req = new Request([
    'items' => [
        [
            'material_id' => $material->id,
            'material_name_or_type' => $material->material_name,
            'width_m' => $width,
            'length_m' => $length,
            'fixed_length_m' => $width,
            'custom_width_cm' => $length * 100,
            'area_m2' => $area,
            'billable_area_m2' => $area,
            'is_custom_banner' => true,
            'dimension_text' => "{$width}m x {$length}m ({$area}m²) - Mata Ayam 4 Sudut",
            'qty' => 1
        ]
    ],
    'payment_method' => 'Cash',
    'customer_name' => 'Bpk. Ahmad Santoso',
    'customer_phone' => '081234567890'
]);

$res = (new \App\Http\Controllers\PosController())->checkout($req);
$data = $res->getData(true);

echo "Actual Total Price from POS Checkout: Rp " . number_format($data['total_price'], 0, ',', '.') . "\n";

if ((float)$data['total_price'] === (float)$expectedPrice) {
    echo "SUCCESS: POS Result matches the modal preview exactly (Rp " . number_format($expectedPrice, 0, ',', '.') . ")!\n";
} else {
    echo "FAIL: Price mismatch! Expected {$expectedPrice}, Got {$data['total_price']}\n";
}

// Test receipt rendering with customer name
$receiptHtml = (new \App\Http\Controllers\SalesController())->receipt($data['transaction_id'])->render();
if (strpos($receiptHtml, 'Bpk. Ahmad Santoso') !== false && strpos($receiptHtml, '081234567890') !== false) {
    echo "SUCCESS: Customer name and phone correctly rendered on 58mm thermal receipt!\n";
} else {
    echo "FAIL: Customer name missing from receipt!\n";
}
