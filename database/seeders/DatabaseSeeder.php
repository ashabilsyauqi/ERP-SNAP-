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

        // 2b. Seed requested Owner users and baseline staff
        User::create([
            'username' => 'SWANTO',
            'role' => 'owner',
            'password' => Hash::make('@2026@snap-erp#'),
            'branch_id' => $pusat->id,
        ]);

        User::create([
            'username' => 'KINGAshabil',
            'role' => 'owner',
            'password' => Hash::make('dukuhzamrud@j7'),
            'branch_id' => $pusat->id,
        ]);

        // Baseline staff per branch
        User::create([
            'username' => 'manager_pusat',
            'role' => 'manager',
            'password' => Hash::make('password123'),
            'branch_id' => $pusat->id,
        ]);

        User::create([
            'username' => 'cashier_pusat',
            'role' => 'cashier',
            'password' => Hash::make('password123'),
            'branch_id' => $pusat->id,
        ]);

        User::create([
            'username' => 'purchasing_pusat',
            'role' => 'purchasing',
            'password' => Hash::make('password123'),
            'branch_id' => $pusat->id,
        ]);

        // 3. Seed Realistic Comprehensive Printing Products
        $this->call(MaterialProductSeeder::class);

        // 4. Seed 3-Month Realistic Sales Data (2jt-5jt daily, +5% monthly growth)
        $this->call(ThreeMonthRealisticSalesSeeder::class);
    }
}
