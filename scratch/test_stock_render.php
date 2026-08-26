<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$user = User::first();
Auth::login($user);

$controller = new \App\Http\Controllers\StockController();
$view = $controller->index(new Request());
$rendered = $view->render();

echo "Stock View Rendered Successfully! Output length: " . strlen($rendered) . " bytes\n";
