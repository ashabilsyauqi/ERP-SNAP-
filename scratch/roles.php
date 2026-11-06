<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo implode(", ", \App\Models\User::distinct()->pluck('role')->toArray()) . PHP_EOL;
