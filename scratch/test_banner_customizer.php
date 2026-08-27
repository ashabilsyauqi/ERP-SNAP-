<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$cashier = User::where('role', 'cashier')->first();
Auth::login($cashier);

echo "Logged in as cashier: {$cashier->username}, Branch: {$cashier->branch_id}\n";

$posController = new \App\Http\Controllers\PosController();
$view = $posController->index();
echo "POS View rendered successfully! Length: " . strlen($view->render()) . "\n";

// Test Checkout with Banner Custom Dimension (e.g. 0.8m x 2.5m -> physical 2m2, billable 2.5m2)
$banner = Material::where('branch_id', $cashier->branch_id)->where('material_name', 'like', '%flexi%')->first()
          ?? Material::where('branch_id', $cashier->branch_id)->first();

echo "Testing banner checkout with material: {$banner->material_name} (Retail: {$banner->retail_price}/m2)\n";

$req = new Request([
    'items' => [
        [
            'material_name_or_type' => $banner->material_name,
            'width_m' => 0.8,
            'length_m' => 2.5,
            'billable_area_m2' => 2.5,
            'area_m2' => 2.0,
            'dimension_text' => '0.80m x 2.50m (Min 1m: 2.50m²) - Mata Ayam 4 Sudut',
            'qty' => 2,
        ]
    ],
    'payment_method' => 'Cash',
    'customer_name' => 'Budi Testing Roll Banner',
    'customer_phone' => '08123456789',
]);

$response = $posController->checkout($req);
$data = $response->getData(true);
echo "Checkout Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

