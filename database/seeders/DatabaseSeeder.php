<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Material;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed branches & accounts first
        $this->call([
            BranchSeeder::class,
            AccountSeeder::class,
        ]);

        $branches = \App\Models\Branch::all();
        $pusat = $branches->firstWhere('nama_cabang', 'Cabang Grand Wisata (Pusat)');
        $grandwis = $pusat;
        $btr = $branches->firstWhere('nama_cabang', 'Cabang BTR Bekasi');
        $tambun = $branches->firstWhere('nama_cabang', 'Cabang Tambun');

        // 2a. Seed Suppliers
        $suppliers = [];
        $supplierData = [
            ['name' => 'Bintang Terang', 'perusahaan' => 'PT Bintang Terang (Bahan Baku)', 'kontak' => '08123456780', 'alamat' => 'Jl. Industri No 1'],
            ['name' => 'Sumber Rejeki', 'perusahaan' => 'CV Sumber Rejeki (Tinta & Kertas)', 'kontak' => '08123456781', 'alamat' => 'Jl. Kertas No 2'],
            ['name' => 'Mitra Sablon', 'perusahaan' => 'Toko Mitra Sablon', 'kontak' => '08123456782', 'alamat' => 'Jl. Sablon No 3'],
        ];

        foreach ($supplierData as $sData) {
            $suppliers[] = \App\Models\Supplier::create($sData);
        }

        // 2b. Seed users with branch relations (including managers)
        User::factory()->create([
            'username' => 'owner1',
            'role' => 'owner',
            'password' => Hash::make('password'),
            'branch_id' => $pusat->id,
        ]);

        User::factory()->create([
            'username' => 'manager_pusat',
            'role' => 'manager',
            'password' => Hash::make('password'),
            'branch_id' => $pusat->id,
        ]);

        User::factory()->create([
            'username' => 'manager_grandwis',
            'role' => 'manager',
            'password' => Hash::make('password'),
            'branch_id' => $grandwis->id,
        ]);

        User::factory()->create([
            'username' => 'manager_btr',
            'role' => 'manager',
            'password' => Hash::make('password'),
            'branch_id' => $btr->id,
        ]);

        User::factory()->create([
            'username' => 'manager_tambun',
            'role' => 'manager',
            'password' => Hash::make('password'),
            'branch_id' => $tambun->id,
        ]);

        User::factory()->create([
            'username' => 'purchasing1',
            'role' => 'purchasing',
            'password' => Hash::make('password'),
            'branch_id' => $pusat->id,
        ]);

        User::factory()->create([
            'username' => 'cashier_pusat',
            'role' => 'cashier',
            'password' => Hash::make('password'),
            'branch_id' => $pusat->id,
        ]);

        User::factory()->create([
            'username' => 'cashier_grandwis',
            'role' => 'cashier',
            'password' => Hash::make('password'),
            'branch_id' => $grandwis->id,
        ]);

        User::factory()->create([
            'username' => 'cashier_btr',
            'role' => 'cashier',
            'password' => Hash::make('password'),
            'branch_id' => $btr->id,
        ]);

        User::factory()->create([
            'username' => 'cashier_tambun',
            'role' => 'cashier',
            'password' => Hash::make('password'),
            'branch_id' => $tambun->id,
        ]);

        User::factory()->create([
            'username' => 'purchasing_grandwis',
            'role' => 'purchasing',
            'password' => Hash::make('password'),
            'branch_id' => $grandwis->id,
        ]);

        User::factory()->create([
            'username' => 'purchasing_btr',
            'role' => 'purchasing',
            'password' => Hash::make('password'),
            'branch_id' => $btr->id,
        ]);

        User::factory()->create([
            'username' => 'purchasing_tambun',
            'role' => 'purchasing',
            'password' => Hash::make('password'),
            'branch_id' => $tambun->id,
        ]);

        // Default materials per-branch (Kantor Pusat)
        foreach ([$pusat, $btr, $tambun] as $branchItem) {
            $b3m = Material::create([
                'branch_id' => $branchItem->id,
                'supplier_id' => $suppliers[0]->id, // Bintang Terang
                'material_name' => 'Kain Banner 3m',
                'fixed_size' => 3.00,
                'purchase_price' => 30000,
                'retail_price' => 45000,
                'stock_qty' => 10
            ]);
            $b3m->wholesalePrices()->create(['min_qty' => 5, 'wholesale_price' => 40000]);

            $b4m = Material::create([
                'branch_id' => $branchItem->id,
                'supplier_id' => $suppliers[0]->id, // Bintang Terang
                'material_name' => 'Kain Banner 4m',
                'fixed_size' => 4.00,
                'purchase_price' => 40000,
                'retail_price' => 60000,
                'stock_qty' => 10
            ]);
            $b4m->wholesalePrices()->create(['min_qty' => 5, 'wholesale_price' => 55000]);

            $a3 = Material::create([
                'branch_id' => $branchItem->id,
                'supplier_id' => $suppliers[1]->id, // Sumber Rejeki
                'material_name' => 'Kertas A3+',
                'fixed_size' => null,
                'purchase_price' => 1000,
                'retail_price' => 3000,
                'stock_qty' => 500
            ]);
            $a3->wholesalePrices()->create(['min_qty' => 100, 'wholesale_price' => 2000]);

            Material::create([
                'branch_id' => $branchItem->id,
                'supplier_id' => $suppliers[1]->id, // Sumber Rejeki
                'material_name' => 'Tinta Generic (OPEX)',
                'fixed_size' => null,
                'purchase_price' => 50000,
                'retail_price' => 0,
                'stock_qty' => 5
            ]);
        }

        // 3. Seed finance demo transactions
        $this->call(FinanceDemoSeeder::class);
    }
}
