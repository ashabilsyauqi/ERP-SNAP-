<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SeedGunawanTestSalesCommand extends Command
{
    protected $signature = 'dummy:seed-machine-sales';
    protected $description = 'Seed realistic dummy test transactions for Mesin Pak Gunawan in September 2026';

    public function handle()
    {
        $this->info('Seeding dummy test transactions for September 2026...');

        $user = User::first();
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->error('No branches found!');
            return 1;
        }

        $customerNames = [
            'PT Sinar Grafika Utama',
            'CV Maju Mandiri Creative',
            'Bpk. Hendra Kurniawan',
            'Ibu Diana Putri',
            'Studio Foto Cahaya',
            'Yayasan Bina Bangsa',
            'Resto Rasa Nusantara',
            'Klinik Sehat Bersama',
            'Percetakan Prima Jaya',
            'Agensi Digital Kreasi',
        ];

        $dates = [
            '2026-09-02 10:15:00',
            '2026-09-05 14:30:00',
            '2026-09-08 11:20:00',
            '2026-09-12 16:45:00',
            '2026-09-15 09:30:00',
            '2026-09-18 13:10:00',
            '2026-09-20 15:00:00',
            '2026-09-22 11:40:00',
            '2026-09-24 10:00:00',
            '2026-09-25 14:15:00',
        ];

        $createdCount = 0;
        $totalOmzet = 0;

        DB::beginTransaction();
        try {
            foreach ($dates as $idx => $dateStr) {
                $branch = $branches->random();
                $custName = $customerNames[$idx % count($customerNames)];
                $dt = Carbon::parse($dateStr);

                // Get materials for this branch
                $gunawanMaterials = Material::where('branch_id', $branch->id)
                    ->where('machine_tag', 'Mesin Pak Gunawan')
                    ->inRandomOrder()
                    ->take(rand(1, 3))
                    ->get();

                // If branch has no gunawan materials, take branch 1
                if ($gunawanMaterials->isEmpty()) {
                    $gunawanMaterials = Material::where('machine_tag', 'Mesin Pak Gunawan')
                        ->inRandomOrder()
                        ->take(rand(1, 3))
                        ->get();
                }

                $nonGunawanMaterials = Material::where('branch_id', $branch->id)
                    ->whereNull('machine_tag')
                    ->inRandomOrder()
                    ->take(rand(0, 1))
                    ->get();

                $allMaterials = $gunawanMaterials->concat($nonGunawanMaterials);

                if ($allMaterials->isEmpty()) {
                    continue;
                }

                $txTotal = 0;
                $txHpp = 0;
                $detailsData = [];

                foreach ($allMaterials as $mat) {
                    $qty = rand(5, 50); // e.g., 5 to 50 sheets / pcs
                    if (str_contains(strtolower($mat->material_name), 'brosur') || str_contains(strtolower($mat->material_name), 'kartu')) {
                        $qty = rand(1, 5); // paket rim / box
                    }
                    $price = (float) $mat->retail_price;
                    $hpp = (float) $mat->purchase_price;
                    $subtotal = $qty * $price;
                    $subHpp = $qty * $hpp;

                    $txTotal += $subtotal;
                    $txHpp += $subHpp;

                    $detailsData[] = [
                        'material_id' => $mat->id,
                        'qty_ordered' => $qty,
                        'selling_price' => $price,
                        'click_charge' => 0,
                        'fixed_length_m' => 0,
                        'custom_width_cm' => 0,
                        'area_m2' => 0,
                        'created_at' => $dt,
                        'updated_at' => $dt,
                    ];
                }

                $invCode = 'INV-' . $dt->format('Ymd') . '-' . strtoupper(Str::random(5));

                $tx = Transaction::create([
                    'invoice_number' => $invCode,
                    'user_id' => $user ? $user->id : 1,
                    'branch_id' => $branch->id,
                    'customer_name' => $custName,
                    'customer_phone' => '0812' . rand(10000000, 99999999),
                    'total_price' => $txTotal,
                    'original_price' => $txTotal,
                    'discount_amount' => 0,
                    'total_hpp' => $txHpp,
                    'payment_method' => rand(0, 1) ? 'Transfer BCA' : 'QRIS',
                    'payment_status' => 'PAID',
                    'paid_amount' => $txTotal,
                    'remaining_amount' => 0,
                    'order_status' => 'completed',
                    'created_at' => $dt,
                    'updated_at' => $dt,
                ]);

                foreach ($detailsData as $d) {
                    $d['transaction_id'] = $tx->id;
                    TransactionDetail::create($d);
                }

                $createdCount++;
                $totalOmzet += $txTotal;
            }

            DB::commit();
            $this->info("Successfully created {$createdCount} dummy transactions with total revenue of Rp " . number_format($totalOmzet, 0, ',', '.'));
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to seed: ' . $e->getMessage());
            return 1;
        }
    }
}
