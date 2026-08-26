<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Supplier;

class RealStoreProductsSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::firstOrCreate(
            ['name' => 'PT Distributor Grafika Utama'],
            [
                'perusahaan' => 'PT Distributor Grafika Utama',
                'kontak' => '021-88991122',
                'alamat' => 'Kawasan Industri Pulogadung Blok C-12, Jakarta Timur',
                'bank_name' => 'BCA',
                'bank_account_number' => '8801928371',
                'bank_account_name' => 'PT Distributor Grafika Utama'
            ]
        );

        $zamrud = Branch::firstOrCreate(
            ['nama_cabang' => 'Cabang Dukuh Zamrud'],
            [
                'alamat' => 'Jl. Dukuh Zamrud Blok B No. 8, Mustikajaya, Kota Bekasi',
                'telepon' => '021-82607890'
            ]
        );

        $branches = Branch::all();
        $jsonPath = base_path('scratch/parsed_zamrud_products.json');
        
        if (!file_exists($jsonPath)) {
            return;
        }

        $products = json_decode(file_get_contents($jsonPath), true);

        foreach ($branches as $branch) {
            foreach ($products as $prod) {
                $name = trim($prod['material_name']);
                $hpp = (float) $prod['purchase_price'];
                $retail = (float) $prod['retail_price'];
                $isRoll = (str_contains(strtolower($name), 'flexy') || str_contains(strtolower($name), 'flexi') || str_contains(strtolower($name), 'albatros') || str_contains(strtolower($name), 'ritrama') || str_contains(strtolower($name), 'oneway') || str_contains(strtolower($name), 'kain banner')) && !str_contains(strtolower($name), 'paket');
                $fixedSize = $isRoll ? 1.0 : null;

                $material = Material::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'material_name' => $name,
                    ],
                    [
                        'supplier_id' => $supplier->id,
                        'category' => $prod['category'] ?? 'Lainnya',
                        'purchase_price' => $hpp,
                        'retail_price' => $retail,
                        'fixed_size' => $fixedSize,
                        'stock_qty' => 100,
                    ]
                );

                $material->wholesalePrices()->delete();
                foreach ($wholesaleList as $ws) {
                    $material->wholesalePrices()->create([
                        'min_qty' => (int) $ws['min_qty'],
                        'wholesale_price' => (float) $ws['price']
                    ]);
                }
            }
        }
    }
}
