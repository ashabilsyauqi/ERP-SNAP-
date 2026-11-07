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
        // 1. Create daily_closing_wastes table
        if (!Schema::hasTable('daily_closing_wastes')) {
            Schema::create('daily_closing_wastes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_closing_report_id')->nullable()->constrained('daily_closing_reports')->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
                $table->foreignId('material_roll_id')->nullable()->constrained('material_rolls')->onDelete('set null');
                $table->decimal('waste_qty', 10, 2)->default(0);
                $table->string('unit', 50)->default('pcs');
                $table->decimal('hpp_per_unit', 12, 2)->default(0);
                $table->decimal('total_hpp', 12, 2)->default(0);
                $table->string('reason', 255)->default('Trial Alignment / Error');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add total_waste_hpp and waste_data to daily_closing_reports
        Schema::table('daily_closing_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_closing_reports', 'total_waste_hpp')) {
                $table->decimal('total_waste_hpp', 12, 2)->default(0)->after('actual_cash');
            }
            if (!Schema::hasColumn('daily_closing_reports', 'waste_data')) {
                $table->text('waste_data')->nullable()->after('expenses_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closing_wastes');

        Schema::table('daily_closing_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_closing_reports', 'total_waste_hpp')) {
                $table->dropColumn('total_waste_hpp');
            }
            if (Schema::hasColumn('daily_closing_reports', 'waste_data')) {
                $table->dropColumn('waste_data');
            }
        });
    }
};