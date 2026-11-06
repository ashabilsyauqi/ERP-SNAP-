<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\Account::where('tipe', 'beban')->get() as $a) {
    echo $a->id . ' | ' . $a->kode_akun . ' | ' . $a->nama_akun . PHP_EOL;
}
