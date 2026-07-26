<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'nama_cabang' => 'Cabang Grand Wisata (Pusat)',
                'alamat' => 'Jl. Grand Wisata Blok AA No. 10',
                'telepon' => '021-88005678'
            ],
            [
                'nama_cabang' => 'Cabang BTR Bekasi',
                'alamat' => 'Jl. Bekasi Timur Regensi No. 15',
                'telepon' => '021-88009012'
            ],
            [
                'nama_cabang' => 'Cabang Tambun',
                'alamat' => 'Jl. Tambun Raya No. 20',
                'telepon' => '021-88003456'
            ]
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
