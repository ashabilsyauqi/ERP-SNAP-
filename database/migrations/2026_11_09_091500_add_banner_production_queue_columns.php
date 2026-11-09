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
        Schema::table('transaction_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_details', 'production_status')) {
                $table->string('production_status', 30)->default('completed')->after('dimension_text');
            }
            if (!Schema::hasColumn('transaction_details', 'printed_at')) {
                $table->timestamp('printed_at')->nullable()->after('production_status');
            }
            if (!Schema::hasColumn('transaction_details', 'printed_by')) {
                $table->foreignId('printed_by')->nullable()->after('printed_at')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('transaction_details', 'print_batch_id')) {
                $table->string('print_batch_id', 50)->nullable()->after('printed_by');
            }
            if (!Schema::hasColumn('transaction_details', 'roll_area_used_m2')) {
                $table->decimal('roll_area_used_m2', 8, 2)->nullable()->after('print_batch_id');
            }
            if (!Schema::hasColumn('transaction_details', 'offcut_area_m2')) {
                $table->decimal('offcut_area_m2', 8, 2)->nullable()->after('roll_area_used_m2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn([
                'production_status',
                'printed_at',
                'printed_by',
                'print_batch_id',
                'roll_area_used_m2',
                'offcut_area_m2'
            ]);
        });
    }
};
