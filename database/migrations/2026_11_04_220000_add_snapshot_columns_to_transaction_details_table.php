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
            $table->string('product_name_snapshot')->nullable()->after('material_id');
            $table->string('unit_snapshot')->nullable()->after('product_name_snapshot');
            $table->decimal('base_hpp_snapshot', 15, 2)->nullable()->after('unit_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn(['product_name_snapshot', 'unit_snapshot', 'base_hpp_snapshot']);
        });
    }
};