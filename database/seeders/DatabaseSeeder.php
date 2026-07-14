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

        $pusat = \App\Models\Branch::first();

        // 2. Seed users with branch relations
        User::factory()->create([
            'username' => 'owner1',
            'role' => 'owner',
            'password' => Hash::make('password'),
            'branch_id' => $pusat->id,
        ]);

        User::factory()->create([
            'username' => 'purchasing1',
            'role' => 'purchasing',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'username' => 'cashier_pusat',
            'role' => 'cashier',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'username' => 'cashier_grandwis',
            'role' => 'cashier',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'username' => 'cashier_btr',
            'role' => 'cashier',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'username' => 'cashier_tambun',
            'role' => 'cashier',
            'password' => Hash::make('password'),
        ]);

        // Default materials
        $banner3m = Material::create([
            'material_name' => 'Kain Banner 3m', 
            'fixed_size' => 3.00, 
            'purchase_price' => 30000, 
            'retail_price' => 45000,
            'stock_qty' => 10
        ]);
        $banner3m->wholesalePrices()->create([
            'min_qty' => 5,
            'wholesale_price' => 40000
        ]);
        
        $banner4m = Material::create([
            'material_name' => 'Kain Banner 4m', 
            'fixed_size' => 4.00, 
            'purchase_price' => 40000, 
            'retail_price' => 60000,
            'stock_qty' => 10
        ]);
        $banner4m->wholesalePrices()->create([
            'min_qty' => 5,
            'wholesale_price' => 55000
        ]);
        
        $kertasA3 = Material::create([
            'material_name' => 'Kertas A3+', 
            'fixed_size' => null, 
            'purchase_price' => 1000, 
            'retail_price' => 3000,
            'stock_qty' => 500
        ]);
        $kertasA3->wholesalePrices()->create([
            'min_qty' => 100,
            'wholesale_price' => 2000
        ]);
        
        Material::create([
            'material_name' => 'Tinta Generic (OPEX)', 
            'fixed_size' => null, 
            'purchase_price' => 50000, 
            'retail_price' => 0,
            'stock_qty' => 5
        ]);

        // 3. Seed finance demo transactions
        $this->call(FinanceDemoSeeder::class);
    }
}
