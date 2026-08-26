<?php

require '/Users/kingashabil/Desktop/Skirpsi/vendor/autoload.php';
$app = require_once '/Users/kingashabil/Desktop/Skirpsi/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use App\Models\Supplier;

// 1. Ensure Default Supplier exists
$supplier = Supplier::firstOrCreate(
    ['name' => 'PT Distributor Grafika Utama'],
    [
        'perusahaan' => 'PT Distributor Grafika Utama',
        'telepon' => '021-88991122',
        'email' => 'order@grafikautama.co.id',
        'alamat' => 'Kawasan Industri Pulogadung Blok C-12, Jakarta Timur',
        'bank_name' => 'BCA',
        'bank_account_number' => '8801928371',
        'bank_account_name' => 'PT Distributor Grafika Utama'
    ]
);

// 2. Ensure Cabang Zamrud exists alongside existing branches
$zamrud = Branch::firstOrCreate(
    ['nama_cabang' => 'Cabang Dukuh Zamrud'],
    [
        'alamat' => 'Jl. Dukuh Zamrud Blok B No. 8, Mustikajaya, Kota Bekasi',
        'telepon' => '021-82607890'
    ]
);

$branches = Branch::all();
echo "Branches found: " . $branches->pluck('nama_cabang')->implode(', ') . "\n\n";

// 3. Load parsed products from JSON
$jsonPath = '/Users/kingashabil/Desktop/Skirpsi/scratch/parsed_zamrud_products.json';
$products = json_decode(file_get_contents($jsonPath), true);
echo "Total unique products to implement per branch: " . count($products) . "\n\n";

$totalCreated = 0;
$totalUpdated = 0;
$totalWholesaleTiers = 0;

foreach ($branches as $branch) {
    echo ">> Processing Branch: {$branch->nama_cabang} (ID: {$branch->id})...\n";
    
    foreach ($products as $prod) {
        $name = trim($prod['material_name']);
        $hpp = (float) $prod['purchase_price'];
        $retail = (float) $prod['retail_price'];
        $fixedSize = (float) $prod['fixed_size'];
        $wholesaleList = $prod['wholesale'] ?? [];

        // Check if material already exists for this branch
        $material = Material::where('branch_id', $branch->id)
            ->where('material_name', $name)
            ->first();

        if ($material) {
            $material->update([
                'supplier_id' => $supplier->id,
                'purchase_price' => $hpp,
                'retail_price' => $retail,
                'fixed_size' => $fixedSize,
            ]);
            $totalUpdated++;
        } else {
            $material = Material::create([
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'material_name' => $name,
                'fixed_size' => $fixedSize,
                'purchase_price' => $hpp,
                'retail_price' => $retail,
                'stock_qty' => 100, // standard default inventory
            ]);
            $totalCreated++;
        }

        // Reset and insert wholesale pricing tiers
        $material->wholesalePrices()->delete();
        foreach ($wholesaleList as $ws) {
            $material->wholesalePrices()->create([
                'min_qty' => (int) $ws['min_qty'],
                'wholesale_price' => (float) $ws['price']
            ]);
            $totalWholesaleTiers++;
        }
    }
}

echo "\n=======================================================\n";
echo "SUCCESSFULLY IMPLEMENTED REAL STORE DATA ACROSS ALL BRANCHES!\n";
echo "Total Branches: " . $branches->count() . "\n";
echo "Total Material Records Created: {$totalCreated}\n";
echo "Total Material Records Updated: {$totalUpdated}\n";
echo "Total Wholesale Tiers Active: {$totalWholesaleTiers}\n";
echo "Total Materials in System Now: " . Material::count() . "\n";
echo "=======================================================\n";
