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
            $table->decimal('fixed_length_m', 10, 2)->nullable()->after('qty_ordered');
            $table->decimal('custom_width_cm', 10, 2)->nullable()->after('fixed_length_m');
            $table->decimal('area_m2', 10, 3)->nullable()->after('custom_width_cm');
            $table->string('dimension_text')->nullable()->after('area_m2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn(['fixed_length_m', 'custom_width_cm', 'area_m2', 'dimension_text']);
        });
    }
};
