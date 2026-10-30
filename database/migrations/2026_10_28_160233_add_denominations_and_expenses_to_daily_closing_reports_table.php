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
        Schema::table('daily_closing_reports', function (Blueprint $table) {
            $table->text('cash_denominations')->nullable()->after('actual_cash');
            $table->text('expenses_data')->nullable()->after('cash_denominations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_closing_reports', function (Blueprint $table) {
            $table->dropColumn(['cash_denominations', 'expenses_data']);
        });
    }
};