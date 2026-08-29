<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset all users' passwords to 123123123 as requested by owner
        $defaultPassword = Hash::make('123123123');
        User::query()->update(['password' => $defaultPassword]);

        // 2. Synchronize master materials across ALL branches (including Samarinda, Zamrud, BTR, etc.)
        $allBranches = Branch::all();
        if ($allBranches->isEmpty()) {
            return;
        }

        // Identify primary branch with most materials as master template
        $masterBranch = Branch::withCount('materials')
            ->orderBy('materials_count', 'desc')
            ->first();

        if (!$masterBranch || $masterBranch->materials_count == 0) {
            return;
        }

        $masterMaterials = Material::where('branch_id', $masterBranch->id)
            ->with('wholesalePrices')
            ->get();

        foreach ($allBranches as $branch) {
            if ($branch->id === $masterBranch->id) {
                continue;
            }

            foreach ($masterMaterials as $template) {
                // Check if material already exists in this branch
                $exists = Material::where('branch_id', $branch->id)
                    ->where('material_name', $template->material_name)
                    ->first();

                if (!$exists) {
                    $newMat = Material::create([
                        'branch_id'      => $branch->id,
                        'category'       => $template->category ?: 'Lainnya',
                        'supplier_id'    => $template->supplier_id,
                        'material_name'  => $template->material_name,
                        'unit'           => $template->unit ?: 'Pcs',
                        'fixed_size'     => $template->fixed_size,
                        'purchase_price' => $template->purchase_price,
                        'retail_price'   => $template->retail_price,
                        'stock_qty'      => 0, // initial stock 0 for branch
                    ]);

                    foreach ($template->wholesalePrices as $wp) {
                        MaterialWholesalePrice::create([
                            'material_id'     => $newMat->id,
                            'min_qty'         => $wp->min_qty,
                            'wholesale_price' => $wp->wholesale_price,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
