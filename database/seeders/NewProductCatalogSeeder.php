<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class NewProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Soft delete all current active materials (preserves historical links)
        DB::table('materials')->whereNull('deleted_at')->update(['deleted_at' => now()]);

        // 2. Load catalog definitions
        $catalog = require __DIR__ . '/catalog_data.php';

        $branches = Branch::all();
        if ($branches->isEmpty()) {
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($branches as $branch) {
                foreach ($catalog as $item) {
                    $mat = Material::create([
                        'branch_id' => $branch->id,
                        'supplier_id' => 1,
                        'material_name' => $item['name'],
                        'category' => $item['category'],
                        'fixed_size' => $item['fixed_size'] ? 1 : null,
                        'purchase_price' => $item['purchase_price'],
                        'retail_price' => $item['retail_price'],
                        'stock_qty' => 500,
                        'has_click_charge' => false,
                        'click_charge' => 0,
                    ]);

                    if (!empty($item['tiers'])) {
                        foreach ($item['tiers'] as $tier) {
                            MaterialWholesalePrice::create([
                                'material_id' => $mat->id,
                                'min_qty' => $tier['min_qty'],
                                'wholesale_price' => $tier['price'],
                            ]);
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}