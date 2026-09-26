<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'fulfillment_branch_id')) {
                $table->foreignId('fulfillment_branch_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('transactions', 'is_cross_branch')) {
                $table->boolean('is_cross_branch')->default(false)->after('fulfillment_branch_id');
            }
            if (!Schema::hasColumn('transactions', 'order_branch_share')) {
                $table->decimal('order_branch_share', 15, 2)->default(0)->after('is_cross_branch');
            }
            if (!Schema::hasColumn('transactions', 'fulfillment_branch_share')) {
                $table->decimal('fulfillment_branch_share', 15, 2)->default(0)->after('order_branch_share');
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_details', 'is_vendor_job')) {
                $table->boolean('is_vendor_job')->default(false)->after('selling_price');
            }
            if (!Schema::hasColumn('transaction_details', 'vendor_name')) {
                $table->string('vendor_name', 150)->nullable()->after('is_vendor_job');
            }
            if (!Schema::hasColumn('transaction_details', 'vendor_cost')) {
                $table->decimal('vendor_cost', 15, 2)->default(0)->after('vendor_name');
            }
            if (!Schema::hasColumn('transaction_details', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('vendor_cost');
            }
            if (!Schema::hasColumn('transaction_details', 'vendor_notes')) {
                $table->text('vendor_notes')->nullable()->after('shipping_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'fulfillment_branch_id')) {
                $table->dropForeign(['fulfillment_branch_id']);
                $table->dropColumn(['fulfillment_branch_id', 'is_cross_branch', 'order_branch_share', 'fulfillment_branch_share']);
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn(['is_vendor_job', 'vendor_name', 'vendor_cost', 'shipping_cost', 'vendor_notes']);
        });
    }
};
