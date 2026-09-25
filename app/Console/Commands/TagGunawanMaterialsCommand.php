<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Material;

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
    protected $description = 'Beri label mesin (Mesin KM) pada produk-produk cetak yang dihasilkan mesin tersebut';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tag = $this->option('tag') ?: 'Mesin KM';

        $this->info("Menandai produk-produk dengan label: [{$tag}]...");

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
            $materials = Material::where('material_name', 'LIKE', '%' . $pattern . '%')->get();
            foreach ($materials as $m) {
                $m->machine_tag = $tag;
                $m->save();
                $this->line("✓ [ID {$m->id}] {$m->material_name} (Cabang ID: {$m->branch_id}) -> {$tag}");
                $updatedCount++;
            }
        }

        $this->info("\n[SELESAI] Total {$updatedCount} produk berhasil dilabeli dengan '{$tag}'!");
        return 0;
    }
}
