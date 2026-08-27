<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "--- 1. Testing Manager Access to Users ---\n";
$manager = User::where('role', 'manager')->first();
if ($manager) {
    Auth::login($manager);
    $req = new \Illuminate\Http\Request();
    $view = (new \App\Http\Controllers\UserController())->index($req);
    $viewData = $view->getData();
    echo "Manager branch_id: " . $manager->branch_id . "\n";
    echo "Total users seen by manager: " . $viewData['users']->count() . "\n";
    foreach ($viewData['users'] as $u) {
        echo " - User: {$u->username} | Role: {$u->role} | Branch ID: {$u->branch_id}\n";
    }
}

echo "\n--- 2. Testing Owner Access to Users ---\n";
$owner = User::where('role', 'owner')->first();
if ($owner) {
    Auth::login($owner);
    $req = new \Illuminate\Http\Request();
    $view = (new \App\Http\Controllers\UserController())->index($req);
    $viewData = $view->getData();
    echo "Total users seen by owner: " . $viewData['users']->count() . "\n";
    foreach ($viewData['users'] as $u) {
        echo " - User: {$u->username} | Role: {$u->role} | Branch: " . ($u->branch->nama_cabang ?? 'Pusat/None') . "\n";
    }
}
