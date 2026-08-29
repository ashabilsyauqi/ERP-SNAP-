<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialWholesalePrice;

class SyncCatalogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snaprint:sync-catalog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh katalog master produk / bahan baku ke semua cabang yang ada dengan stok awal 0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi master katalog bahan baku ke semua cabang...');

        $allBranches = Branch::all();
        if ($allBranches->isEmpty()) {
            $this->warn('Tidak ada cabang yang terdaftar.');
            return 0;
        }

        // Identify primary master branch with most materials
        $masterBranch = Branch::withCount('materials')
            ->orderBy('materials_count', 'desc')
            ->first();

        if (!$masterBranch || $masterBranch->materials_count == 0) {
            $this->warn('Belum ada master bahan baku pada cabang utama.');
            return 0;
        }

        $masterMaterials = Material::where('branch_id', $masterBranch->id)
            ->with('wholesalePrices')
            ->get();

        $this->info("Menggunakan '{$masterBranch->nama_cabang}' sebagai master referensi ({$masterMaterials->count()} produk).");

        $totalCreated = 0;

        foreach ($allBranches as $branch) {
            if ($branch->id === $masterBranch->id) {
                continue;
            }

            $branchCreated = 0;
            foreach ($masterMaterials as $template) {
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
                        'stock_qty'      => 0,
                    ]);

                    foreach ($template->wholesalePrices as $wp) {
                        MaterialWholesalePrice::create([
                            'material_id'     => $newMat->id,
                            'min_qty'         => $wp->min_qty,
                            'wholesale_price' => $wp->wholesale_price,
                        ]);
                    }

                    $branchCreated++;
                    $totalCreated++;
                }
            }

            $currentTotal = Material::where('branch_id', $branch->id)->count();
            $this->line("✅ Cabang '{$branch->nama_cabang}': +{$branchCreated} produk baru ditambahkan (Total sekarang: {$currentTotal} produk).");
        }

        $this->info("🎉 Selesai! Total {$totalCreated} produk berhasil disinkronkan ke seluruh cabang.");
        return 0;
    }
}
