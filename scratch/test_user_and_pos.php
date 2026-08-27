<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$owner = User::where('role', 'owner')->first();
Auth::login($owner);

$posController = new \App\Http\Controllers\PosController();
$response = $posController->index();

echo "Owner accessing POS response class: " . get_class($response) . "\n";
if ($response instanceof \Illuminate\Http\RedirectResponse) {
    echo "Redirect URL: " . $response->getTargetUrl() . "\n";
}

$testUser = User::create([
    'username' => 'test_delete_user_' . uniqid(),
    'password' => 'secret123',
    'role' => 'cashier',
    'branch_id' => 1
]);

echo "Created test user: ID {$testUser->id}, Username: {$testUser->username}\n";

$userController = new \App\Http\Controllers\UserController();
$delResponse = $userController->destroy($testUser);

echo "Delete response class: " . get_class($delResponse) . "\n";
echo "User deleted_at: " . ($testUser->fresh() ? ($testUser->fresh()->deleted_at ?? 'Active') : 'Deleted') . "\n";
