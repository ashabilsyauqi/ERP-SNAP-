<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// 1. Test Manager
$manager = User::where('role', 'manager')->first();
if ($manager) {
    Auth::login($manager);
    $view = (new \App\Http\Controllers\OwnerController())->dashboard(new \Illuminate\Http\Request());
    echo "Manager Dashboard rendered: " . ($view ? "OK (Length: " . strlen($view->render()) . ")" : "FAIL") . "\n";

    // Test manager accessing POS index
    $posRes = (new \App\Http\Controllers\PosController())->index();
    echo "Manager accessing POS index redirect: " . ($posRes instanceof \Illuminate\Http\RedirectResponse ? $posRes->getTargetUrl() : "NOT REDIRECT") . "\n";
} else {
    echo "No manager user found\n";
}

// 2. Test Owner
$owner = User::where('role', 'owner')->first();
if ($owner) {
    Auth::login($owner);
    $view = (new \App\Http\Controllers\OwnerController())->dashboard(new \Illuminate\Http\Request());
    echo "Owner Dashboard rendered: " . ($view ? "OK (Length: " . strlen($view->render()) . ")" : "FAIL") . "\n";
}

// 3. Test Cashier
$cashier = User::where('role', 'cashier')->first();
if ($cashier) {
    Auth::login($cashier);
    $view = (new \App\Http\Controllers\SalesController())->index(new \Illuminate\Http\Request());
    echo "Cashier Sales rendered: " . ($view ? "OK (Length: " . strlen($view->render()) . ")" : "FAIL") . "\n";
}
