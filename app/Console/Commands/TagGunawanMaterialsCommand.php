<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Material;
use App\Models\Branch;

class TagGunawanMaterialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'materials:tag-km {--tag=Mesin KM : Nama label mesin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Beri label mesin (Mesin KM) khusus untuk produk di Cabang Grand Wisata (Pusat)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tag = $this->option('tag') ?: 'Mesin KM';

        $branch = Branch::where('nama_cabang', 'LIKE', '%Grand Wisata%')->first();
        $branchId = $branch ? $branch->id : 1;

        $this->info("Menandai produk-produk dengan label: [{$tag}] khusus di Cabang Grand Wisata (ID: {$branchId})...");

        // Reset tags for other branches
        Material::where('branch_id', '!=', $branchId)->where('machine_tag', $tag)->update(['machine_tag' => null]);

        $targetPatterns = [
            'Print Art paper',
            'Print Art carton',
            'Print Blus white',
            'Print Linen',
            'Sticker Cromo',
            'Sticker vinyl Glossy',
            'Brosur',
            'Kartu Nama',
            'Kartu nama',
        ];

        $updatedCount = 0;

        foreach ($targetPatterns as $pattern) {
            $materials = Material::where('branch_id', $branchId)
                ->where('material_name', 'LIKE', '%' . $pattern . '%')
                ->get();

            foreach ($materials as $m) {
                $m->machine_tag = $tag;
                $m->save();
                $this->line("✓ [ID {$m->id}] {$m->material_name} (Cabang: {$branch->nama_cabang}) -> {$tag}");
                $updatedCount++;
            }
        }

        $this->info("\n[SELESAI] Total {$updatedCount} produk di Cabang Grand Wisata berhasil dilabeli dengan '{$tag}'!");
        return 0;
    }
}
