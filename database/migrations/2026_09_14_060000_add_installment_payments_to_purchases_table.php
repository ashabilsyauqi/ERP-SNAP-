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
        // 1. Add paid_amount and remaining_amount to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_cost');
            }
            if (!Schema::hasColumn('purchases', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->nullable()->after('paid_amount');
            }
        });

        // 2. Add purchase_id to purchase_plan_payments table
        Schema::table('purchase_plan_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_plan_payments', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->after('purchase_plan_id')->constrained('purchases')->nullOnDelete();
            }
        });

        // If not SQLite, change purchase_plan_id to nullable
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('purchase_plan_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('purchase_plan_id')->nullable()->change();
            });
        }

        // 3. Backfill existing single purchases
        DB::statement("UPDATE purchases SET paid_amount = total_cost, remaining_amount = 0 WHERE payment_status = 'paid'");
        DB::statement("UPDATE purchases SET paid_amount = 0, remaining_amount = total_cost WHERE payment_status != 'paid' OR payment_status IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_plan_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_plan_payments', 'purchase_id')) {
                $table->dropForeign(['purchase_id']);
                $table->dropColumn('purchase_id');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('purchases', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }
        });
    }
};
