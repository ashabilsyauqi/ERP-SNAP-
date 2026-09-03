<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Account;
use App\Models\Material;
use App\Models\Branch;
use App\Models\MaterialWholesalePrice;

return new class extends Migration {
    public function up(): void {
        // 1. Create daily_closing_reports table
        if (!Schema::hasTable('daily_closing_reports')) {
            Schema::create('daily_closing_reports', function (Blueprint $table) {
                $table->id();
                $table->string('report_number', 50)->unique();
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Manager
                $table->date('closing_date');
                $table->string('shift_type', 50)->default('Full Day');
                $table->integer('total_orders_count')->default(0);
                $table->decimal('total_cash_sales', 15, 2)->default(0);
                $table->decimal('total_transfer_sales', 15, 2)->default(0);
                $table->decimal('total_qris_sales', 15, 2)->default(0);
                $table->decimal('total_sales', 15, 2)->default(0);
                $table->decimal('total_cash_in', 15, 2)->default(0);
                $table->decimal('total_cash_out', 15, 2)->default(0);
                $table->decimal('opening_cash', 15, 2)->default(0);
                $table->decimal('expected_cash', 15, 2)->default(0);
                $table->decimal('actual_cash', 15, 2)->default(0);
                $table->decimal('cash_difference', 15, 2)->default(0);
                $table->integer('click_counter_start')->nullable();
                $table->integer('click_counter_end')->nullable();
                $table->integer('click_count_total')->nullable();
                $table->text('production_notes')->nullable();
                
                // Manager Digital Signature
                $table->string('manager_signature_path')->nullable();
                $table->timestamp('manager_signed_at')->nullable();
                
                // Owner Digital Signature
                $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('owner_signature_path')->nullable();
                $table->timestamp('owner_signed_at')->nullable();
                $table->text('owner_notes')->nullable();
                
                $table->enum('status', ['submitted', 'verified', 'revision'])->default('submitted');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Add Clicked Charged COA account
        if (Schema::hasTable('accounts')) {
            $existing = Account::withTrashed()->where('kode_akun', '5-1100')->first();
            if ($existing) {
                $existing->restore();
                $existing->update([
                    'nama_akun' => 'Clicked Charged',
                    'tipe' => 'beban',
                    'deskripsi' => 'Beban Click Charge Mesin Digital Printing per Lembar / Klik',
                    'is_active' => true,
                ]);
            } else {
                Account::create([
                    'kode_akun' => '5-1100',
                    'nama_akun' => 'Clicked Charged',
                    'tipe' => 'beban',
                    'deskripsi' => 'Beban Click Charge Mesin Digital Printing per Lembar / Klik',
                    'is_active' => true,
                ]);
            }
        }

        // 3. Auto cross-synchronize all products across all branches
        if (Schema::hasTable('materials') && Schema::hasTable('branches')) {
            $branches = Branch::all();
            $distinctMaterials = Material::select('material_name', 'category', 'supplier_id', 'unit', 'fixed_size', 'purchase_price', 'retail_price')
                ->distinct()
                ->get();

            foreach ($branches as $branch) {
                foreach ($distinctMaterials as $proto) {
                    $mat = Material::where('branch_id', $branch->id)
                        ->where('material_name', $proto->material_name)
                        ->first();

                    if (!$mat) {
                        $newMat = Material::create([
                            'branch_id' => $branch->id,
                            'category' => $proto->category ?: 'Lainnya',
                            'supplier_id' => $proto->supplier_id,
                            'material_name' => $proto->material_name,
                            'unit' => $proto->unit ?: 'Pcs',
                            'fixed_size' => $proto->fixed_size,
                            'purchase_price' => $proto->purchase_price,
                            'retail_price' => $proto->retail_price,
                            'stock_qty' => 0,
                        ]);

                        // Copy wholesale prices if source exists
                        $sourceMat = Material::where('material_name', $proto->material_name)->with('wholesalePrices')->first();
                        if ($sourceMat && $sourceMat->wholesalePrices->isNotEmpty()) {
                            foreach ($sourceMat->wholesalePrices as $wp) {
                                MaterialWholesalePrice::create([
                                    'material_id' => $newMat->id,
                                    'min_qty' => $wp->min_qty,
                                    'wholesale_price' => $wp->wholesale_price,
                                ]);
                            }
                        }
                    } else {
                        // Ensure category is never empty
                        if (empty($mat->category)) {
                            $mat->update(['category' => $proto->category ?: 'Lainnya']);
                        }
                    }
                }
            }
        }
    }

    public function down(): void {
        Schema::dropIfExists('daily_closing_reports');
    }
};
