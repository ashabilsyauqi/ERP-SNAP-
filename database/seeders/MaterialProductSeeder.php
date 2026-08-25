<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Material;

class MaterialProductSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            return;
        }

        $suppliers = Supplier::all();
        $sBintang = $suppliers->firstWhere('name', 'Bintang Terang') ?? $suppliers->first();
        $sSumber = $suppliers->firstWhere('name', 'Sumber Rejeki') ?? $suppliers->first();
        $sMitra = $suppliers->firstWhere('name', 'Mitra Sablon') ?? $suppliers->first();

        // Kategori Lengkap Produk & Bahan Percetakan / Digital Printing
        $productsCatalog = [
            // --- 1. BANNER & OUTDOOR MEDIA ---
            [
                'name' => 'Banner Flexi China 280gsm',
                'fixed_size' => null, // per meter persegi
                'purchase_price' => 12000,
                'retail_price' => 25000,
                'stock_qty' => 150,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 20000],
                    ['min_qty' => 50, 'wholesale_price' => 17000],
                ]
            ],
            [
                'name' => 'Banner Flexi Korea 440gsm (High Quality)',
                'fixed_size' => null,
                'purchase_price' => 22000,
                'retail_price' => 45000,
                'stock_qty' => 100,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 38000],
                    ['min_qty' => 30, 'wholesale_price' => 32000],
                ]
            ],
            [
                'name' => 'Banner Flexi Backlite 510gsm (Neon Box)',
                'fixed_size' => null,
                'purchase_price' => 35000,
                'retail_price' => 65000,
                'stock_qty' => 60,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 55000],
                ]
            ],
            [
                'name' => 'Banner Kain Textile / Cloth Banner',
                'fixed_size' => null,
                'purchase_price' => 28000,
                'retail_price' => 55000,
                'stock_qty' => 80,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 45000],
                ]
            ],

            // --- 2. DISPLAY & STAND BANNER ---
            [
                'name' => 'X-Banner 60x160cm (Stand Fiber + Cetak Flexi)',
                'fixed_size' => null,
                'purchase_price' => 35000,
                'retail_price' => 65000,
                'stock_qty' => 35,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 55000],
                    ['min_qty' => 20, 'wholesale_price' => 48000],
                ]
            ],
            [
                'name' => 'Y-Banner 60x160cm (Stand Besi + Cetak Luster)',
                'fixed_size' => null,
                'purchase_price' => 45000,
                'retail_price' => 85000,
                'stock_qty' => 25,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 75000],
                ]
            ],
            [
                'name' => 'Roll Up Banner 60x160cm (Alumunium Luxury)',
                'fixed_size' => null,
                'purchase_price' => 85000,
                'retail_price' => 150000,
                'stock_qty' => 20,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 3, 'wholesale_price' => 135000],
                    ['min_qty' => 10, 'wholesale_price' => 120000],
                ]
            ],
            [
                'name' => 'Roll Up Banner 80x200cm (Alumunium Luxury)',
                'fixed_size' => null,
                'purchase_price' => 110000,
                'retail_price' => 195000,
                'stock_qty' => 15,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 3, 'wholesale_price' => 175000],
                    ['min_qty' => 10, 'wholesale_price' => 160000],
                ]
            ],
            [
                'name' => 'Tripod Banner Double Side (Include Frame Impraboard)',
                'fixed_size' => null,
                'purchase_price' => 75000,
                'retail_price' => 140000,
                'stock_qty' => 20,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 125000],
                ]
            ],
            [
                'name' => 'Event Desk Portable / Meja Promosi PVC',
                'fixed_size' => null,
                'purchase_price' => 420000,
                'retail_price' => 650000,
                'stock_qty' => 8,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 3, 'wholesale_price' => 580000],
                ]
            ],

            // --- 3. STIKER & LABEL PRINTING ---
            [
                'name' => 'Stiker Vinyl Glossy / Matte A3+ (Print + Kiss Cut)',
                'fixed_size' => null,
                'purchase_price' => 3500,
                'retail_price' => 10000,
                'stock_qty' => 400,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 8500],
                    ['min_qty' => 50, 'wholesale_price' => 7000],
                    ['min_qty' => 100, 'wholesale_price' => 6000],
                ]
            ],
            [
                'name' => 'Stiker Chromo A3+ (Print + Kiss Cut)',
                'fixed_size' => null,
                'purchase_price' => 2500,
                'retail_price' => 7500,
                'stock_qty' => 500,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 6000],
                    ['min_qty' => 50, 'wholesale_price' => 5000],
                ]
            ],
            [
                'name' => 'Stiker Transparan / Clear A3+',
                'fixed_size' => null,
                'purchase_price' => 4000,
                'retail_price' => 11000,
                'stock_qty' => 250,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 9500],
                    ['min_qty' => 50, 'wholesale_price' => 8000],
                ]
            ],
            [
                'name' => 'Stiker Hologram Custom A3+ (Security/Label)',
                'fixed_size' => null,
                'purchase_price' => 7000,
                'retail_price' => 18000,
                'stock_qty' => 150,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 15000],
                    ['min_qty' => 50, 'wholesale_price' => 13000],
                ]
            ],
            [
                'name' => 'Stiker One Way Vision (Kaca Mobil/Gedung per m2)',
                'fixed_size' => null,
                'purchase_price' => 40000,
                'retail_price' => 75000,
                'stock_qty' => 60,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 65000],
                ]
            ],
            [
                'name' => 'Stiker Sandblast Buram (Kaca Kantor per m2)',
                'fixed_size' => null,
                'purchase_price' => 38000,
                'retail_price' => 70000,
                'stock_qty' => 50,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 60000],
                ]
            ],

            // --- 4. DIGITAL OFFSET / DOKUMEN & KERTAS ---
            [
                'name' => 'Print Art Paper 150gsm A3+',
                'fixed_size' => null,
                'purchase_price' => 1200,
                'retail_price' => 3500,
                'stock_qty' => 600,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 2800],
                    ['min_qty' => 200, 'wholesale_price' => 2200],
                ]
            ],
            [
                'name' => 'Print Art Carton 260gsm A3+',
                'fixed_size' => null,
                'purchase_price' => 1500,
                'retail_price' => 4500,
                'stock_qty' => 600,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 3500],
                    ['min_qty' => 200, 'wholesale_price' => 2800],
                ]
            ],
            [
                'name' => 'Print Art Carton 310gsm A3+',
                'fixed_size' => null,
                'purchase_price' => 1800,
                'retail_price' => 5500,
                'stock_qty' => 400,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 4200],
                    ['min_qty' => 200, 'wholesale_price' => 3500],
                ]
            ],
            [
                'name' => 'Print Kertas HVS 80gsm A4 (Warna/Fullcolor)',
                'fixed_size' => null,
                'purchase_price' => 300,
                'retail_price' => 1500,
                'stock_qty' => 2000,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 100, 'wholesale_price' => 1000],
                    ['min_qty' => 500, 'wholesale_price' => 750],
                ]
            ],
            [
                'name' => 'Print Kertas Jasmine / Linen Mewah A3+',
                'fixed_size' => null,
                'purchase_price' => 2500,
                'retail_price' => 7000,
                'stock_qty' => 200,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 5500],
                ]
            ],

            // --- 5. PERLENGKAPAN KANTOR & ATRIBUT ---
            [
                'name' => 'Kartu Nama 1 Box (100 pcs Art Carton 260 + Laminasi)',
                'fixed_size' => null,
                'purchase_price' => 15000,
                'retail_price' => 35000,
                'stock_qty' => 80,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 30000],
                    ['min_qty' => 20, 'wholesale_price' => 25000],
                ]
            ],
            [
                'name' => 'Brosur / Flyer A5 Art Paper (1 Rim / 500 lbr 2 Sisi)',
                'fixed_size' => null,
                'purchase_price' => 110000,
                'retail_price' => 220000,
                'stock_qty' => 40,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 3, 'wholesale_price' => 195000],
                    ['min_qty' => 10, 'wholesale_price' => 175000],
                ]
            ],
            [
                'name' => 'ID Card PVC (Standar Kartu ATM) + Print 2 Sisi',
                'fixed_size' => null,
                'purchase_price' => 3500,
                'retail_price' => 10000,
                'stock_qty' => 300,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 20, 'wholesale_price' => 8000],
                    ['min_qty' => 100, 'wholesale_price' => 6000],
                ]
            ],
            [
                'name' => 'Lanyard / Tali ID Card Custom Sablon / Printing (2cm)',
                'fixed_size' => null,
                'purchase_price' => 5000,
                'retail_price' => 15000,
                'stock_qty' => 250,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 20, 'wholesale_price' => 12000],
                    ['min_qty' => 100, 'wholesale_price' => 9500],
                ]
            ],
            [
                'name' => 'Stempel Otomatis / Flash Custom (Warna)',
                'fixed_size' => null,
                'purchase_price' => 25000,
                'retail_price' => 65000,
                'stock_qty' => 50,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 3, 'wholesale_price' => 55000],
                ]
            ],
            [
                'name' => 'Nota / Faktur / Surat Jalan NCR 2 Rangkap (1 Buku / 50 set)',
                'fixed_size' => null,
                'purchase_price' => 7000,
                'retail_price' => 18000,
                'stock_qty' => 100,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 15000],
                    ['min_qty' => 50, 'wholesale_price' => 12000],
                ]
            ],
            [
                'name' => 'Stopmap Folio / Map Ijazah Custom Art Carton 310gsm',
                'fixed_size' => null,
                'purchase_price' => 4500,
                'retail_price' => 12000,
                'stock_qty' => 150,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 9500],
                    ['min_qty' => 200, 'wholesale_price' => 8000],
                ]
            ],

            // --- 6. MERCHANDISE & SOUVENIR ---
            [
                'name' => 'Mug Keramik Custom Sablon Sublimasi + Box',
                'fixed_size' => null,
                'purchase_price' => 11000,
                'retail_price' => 25000,
                'stock_qty' => 120,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 12, 'wholesale_price' => 20000],
                    ['min_qty' => 50, 'wholesale_price' => 17000],
                    ['min_qty' => 100, 'wholesale_price' => 15000],
                ]
            ],
            [
                'name' => 'Tumbler Custom Grafir Laser / UV Print',
                'fixed_size' => null,
                'purchase_price' => 28000,
                'retail_price' => 55000,
                'stock_qty' => 60,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 48000],
                    ['min_qty' => 50, 'wholesale_price' => 42000],
                ]
            ],
            [
                'name' => 'Pin Peniti Custom 58mm (Glossy / Doff)',
                'fixed_size' => null,
                'purchase_price' => 1200,
                'retail_price' => 4500,
                'stock_qty' => 300,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 3000],
                    ['min_qty' => 200, 'wholesale_price' => 2200],
                ]
            ],
            [
                'name' => 'Gantungan Kunci Akrilik Custom UV Print (2 Sisi)',
                'fixed_size' => null,
                'purchase_price' => 3500,
                'retail_price' => 10000,
                'stock_qty' => 150,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 25, 'wholesale_price' => 7500],
                    ['min_qty' => 100, 'wholesale_price' => 6000],
                ]
            ],
            [
                'name' => 'Plakat Akrilik Wisuda / Penghargaan Custom (Tebal 5mm)',
                'fixed_size' => null,
                'purchase_price' => 45000,
                'retail_price' => 95000,
                'stock_qty' => 30,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 85000],
                    ['min_qty' => 20, 'wholesale_price' => 75000],
                ]
            ],
            [
                'name' => 'Goodie Bag / Tas Spunbond Custom Sablon (30x40cm)',
                'fixed_size' => null,
                'purchase_price' => 2800,
                'retail_price' => 6500,
                'stock_qty' => 350,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 5000],
                    ['min_qty' => 200, 'wholesale_price' => 4200],
                ]
            ],

            // --- 7. APPAREL & SABLON DTF ---
            [
                'name' => 'Cetak DTF (Direct to Film) Meteran (lebar 58cm x 100cm)',
                'fixed_size' => null,
                'purchase_price' => 25000,
                'retail_price' => 50000,
                'stock_qty' => 100,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 42000],
                    ['min_qty' => 20, 'wholesale_price' => 35000],
                ]
            ],
            [
                'name' => 'Kaos Polos Cotton Combed 30s + Sablon DTF A4 (1 Sisi)',
                'fixed_size' => null,
                'purchase_price' => 38000,
                'retail_price' => 75000,
                'stock_qty' => 80,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 12, 'wholesale_price' => 65000],
                    ['min_qty' => 50, 'wholesale_price' => 58000],
                ]
            ],
            [
                'name' => 'Topi Jaring / Trucker Custom DTF',
                'fixed_size' => null,
                'purchase_price' => 12000,
                'retail_price' => 25000,
                'stock_qty' => 90,
                'supplier_id' => $sMitra?->id,
                'wholesales' => [
                    ['min_qty' => 12, 'wholesale_price' => 20000],
                ]
            ],

            // --- 8. FINISHING & JILID ---
            [
                'name' => 'Jilid Hardcover Skripsi / Laporan (Emas Emboss)',
                'fixed_size' => null,
                'purchase_price' => 15000,
                'retail_price' => 40000,
                'stock_qty' => 50,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 5, 'wholesale_price' => 35000],
                ]
            ],
            [
                'name' => 'Jilid Spiral Kawat + Cover Mika A4',
                'fixed_size' => null,
                'purchase_price' => 4000,
                'retail_price' => 12000,
                'stock_qty' => 150,
                'supplier_id' => $sSumber?->id,
                'wholesales' => [
                    ['min_qty' => 10, 'wholesale_price' => 9500],
                ]
            ],
            [
                'name' => 'Laminasi Panas Doff / Glossy A3+ (per lembar)',
                'fixed_size' => null,
                'purchase_price' => 600,
                'retail_price' => 2500,
                'stock_qty' => 500,
                'supplier_id' => $sBintang?->id,
                'wholesales' => [
                    ['min_qty' => 50, 'wholesale_price' => 1800],
                    ['min_qty' => 200, 'wholesale_price' => 1300],
                ]
            ],
        ];

        // Seed produk ke setiap cabang (Grand Wisata / Pusat, BTR, Tambun)
        foreach ($branches as $branch) {
            foreach ($productsCatalog as $item) {
                $material = Material::create([
                    'branch_id' => $branch->id,
                    'supplier_id' => $item['supplier_id'],
                    'material_name' => $item['name'],
                    'fixed_size' => $item['fixed_size'],
                    'purchase_price' => $item['purchase_price'],
                    'retail_price' => $item['retail_price'],
                    'stock_qty' => $item['stock_qty'],
                ]);

                if (!empty($item['wholesales'])) {
                    foreach ($item['wholesales'] as $wPrice) {
                        $material->wholesalePrices()->create($wPrice);
                    }
                }
            }
        }
    }
}
