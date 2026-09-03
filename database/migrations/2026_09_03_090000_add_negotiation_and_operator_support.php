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
            if (!Schema::hasColumn('transactions', 'original_price')) {
                $table->decimal('original_price', 15, 2)->nullable()->after('total_price');
            }
            if (!Schema::hasColumn('transactions', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('original_price');
            }
            if (!Schema::hasColumn('transactions', 'negotiation_notes')) {
                $table->string('negotiation_notes')->nullable()->after('discount_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'discount_amount', 'negotiation_notes']);
        });
    }
};
