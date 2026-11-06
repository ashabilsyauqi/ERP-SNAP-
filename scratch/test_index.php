<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = \App\Models\User::where('username', 'KINGAshabil')->first();
\Illuminate\Support\Facades\Auth::login($u);

$c = new \App\Http\Controllers\DailyClosingController();
$req = \Illuminate\Http\Request::create('/daily-closing', 'GET');
$v = $c->index($req);
foreach ($v->getData()['reports'] as $r) {
    echo " -> [" . $r->report_number . "] " . $r->closing_date->format('Y-m-d') . " | " . $r->branch->nama_cabang . " | Status: " . $r->status . " | Aktual: Rp " . number_format($r->actual_cash) . PHP_EOL;
}
