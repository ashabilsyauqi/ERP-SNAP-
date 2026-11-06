<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('material_rolls')) {
            Schema::create('material_rolls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
                $table->string('roll_name', 100);
                $table->decimal('roll_width_m', 8, 2);
                $table->decimal('current_length_m', 10, 2)->default(70.00);
                $table->decimal('initial_length_m', 10, 2)->default(70.00);
                $table->timestamps();

                $table->index(['branch_id', 'material_id']);
            });
        }

        Schema::table('transaction_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_details', 'material_roll_id')) {
                $table->unsignedBigInteger('material_roll_id')->nullable()->after('material_id');
            }
            if (!Schema::hasColumn('transaction_details', 'cut_length_m')) {
                $table->decimal('cut_length_m', 10, 2)->nullable()->after('area_m2');
            }
            if (!Schema::hasColumn('transaction_details', 'roll_width_snapshot')) {
                $table->decimal('roll_width_snapshot', 8, 2)->nullable()->after('cut_length_m');
            }
            if (!Schema::hasColumn('transaction_details', 'offcut_width_cm')) {
                $table->integer('offcut_width_cm')->nullable()->after('roll_width_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_details', 'offcut_width_cm')) {
                $table->dropColumn('offcut_width_cm');
            }
            if (Schema::hasColumn('transaction_details', 'roll_width_snapshot')) {
                $table->dropColumn('roll_width_snapshot');
            }
            if (Schema::hasColumn('transaction_details', 'cut_length_m')) {
                $table->dropColumn('cut_length_m');
            }
            if (Schema::hasColumn('transaction_details', 'material_roll_id')) {
                $table->dropColumn('material_roll_id');
            }
        });

        Schema::dropIfExists('material_rolls');
    }
};
