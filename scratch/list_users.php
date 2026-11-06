<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\User::all() as $u) {
    echo "User: [" . $u->id . "] " . $u->username . " | " . $u->role . " | " . $u->full_name . " | Branch: " . $u->branch_id . PHP_EOL;
}
