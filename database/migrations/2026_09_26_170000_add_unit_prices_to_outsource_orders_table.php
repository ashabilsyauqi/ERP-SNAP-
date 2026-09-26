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
        Schema::table('outsource_orders', function (Blueprint $table) {
            $table->decimal('customer_unit_price', 15, 2)->default(0)->after('unit');
            $table->decimal('vendor_unit_price', 15, 2)->default(0)->after('vendor_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outsource_orders', function (Blueprint $table) {
            $table->dropColumn(['customer_unit_price', 'vendor_unit_price']);
        });
    }
};
