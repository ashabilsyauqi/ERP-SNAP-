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

$controller = new \App\Http\Controllers\PosController();
$view = $controller->index();
$rendered = $view->render();

echo "POS View Rendered Successfully! Output length: " . strlen($rendered) . " bytes\n";
