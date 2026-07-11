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
            $table->dropColumn(['wholesale_min_qty', 'wholesale_price']);
        });

        Schema::create('material_wholesale_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->integer('min_qty');
            $table->decimal('wholesale_price', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_wholesale_prices');

        Schema::table('materials', function (Blueprint $table) {
            $table->integer('wholesale_min_qty')->nullable();
            $table->decimal('wholesale_price', 15, 2)->nullable();
        });
    }
};
