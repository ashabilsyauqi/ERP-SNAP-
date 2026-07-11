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
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('retail_price', 15, 2)->default(0)->after('purchase_price');
            $table->integer('wholesale_min_qty')->nullable()->after('retail_price');
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('wholesale_min_qty');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_method')->default('Cash')->after('total_hpp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['retail_price', 'wholesale_min_qty', 'wholesale_price']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
