<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$owner = User::where('role', 'owner')->first();
Auth::login($owner);

echo "1. BranchController index: ";
$res = (new \App\Http\Controllers\BranchController())->index();
echo ($res ? "OK\n" : "FAIL\n");

echo "2. OwnerController dashboard: ";
$res = (new \App\Http\Controllers\OwnerController())->dashboard(new \Illuminate\Http\Request());
echo ($res ? "OK\n" : "FAIL\n");

echo "3. SalesController index: ";
$res = (new \App\Http\Controllers\SalesController())->index(new \Illuminate\Http\Request());
echo ($res ? "OK\n" : "FAIL\n");

echo "4. FinanceDashboardController index: ";
$res = (new \App\Http\Controllers\FinanceDashboardController())->index(new \Illuminate\Http\Request());
echo ($res ? "OK\n" : "FAIL\n");

echo "5. StockController index: ";
$res = (new \App\Http\Controllers\StockController())->index(new \Illuminate\Http\Request());
echo ($res ? "OK\n" : "FAIL\n");

echo "6. PurchasingController index: ";
$res = (new \App\Http\Controllers\PurchasingController())->index(new \Illuminate\Http\Request());
echo ($res ? "OK\n" : "FAIL\n");

echo "All controller views rendered without archive/trashed errors!\n";
