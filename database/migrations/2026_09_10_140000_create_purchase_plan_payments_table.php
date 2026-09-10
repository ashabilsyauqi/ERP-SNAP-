<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create purchase_plan_payments table
        if (!Schema::hasTable('purchase_plan_payments')) {
            Schema::create('purchase_plan_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_plan_id')->constrained('purchase_plans')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('cash_transaction_id')->nullable()->constrained('cash_transactions')->nullOnDelete();
                $table->string('payment_step', 20); // 'step_1' (DP), 'step_2' (Termin 2), 'pelunasan' (Pelunasan)
                $table->string('payment_step_label', 100); // e.g. 'Pembayaran 1 (DP)', 'Pembayaran ke-2 (Termin 2)', 'Pelunasan'
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 100);
                $table->string('payment_reference', 255)->nullable();
                $table->text('payment_notes')->nullable();
                $table->timestamp('paid_at');
                $table->timestamps();
            });
        }

        // 2. Add paid_amount and remaining_amount to purchase_plans table
        Schema::table('purchase_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_plans', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_estimated_cost');
            }
            if (!Schema::hasColumn('purchase_plans', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->nullable()->after('paid_amount');
            }
        });

        // 3. Initialize historical data for existing plans
        DB::statement("UPDATE purchase_plans SET paid_amount = total_estimated_cost, remaining_amount = 0 WHERE payment_status = 'paid'");
        DB::statement("UPDATE purchase_plans SET paid_amount = 0, remaining_amount = total_estimated_cost WHERE payment_status != 'paid' OR payment_status IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_plan_payments');

        Schema::table('purchase_plans', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_plans', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('purchase_plans', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }
        });
    }
};
