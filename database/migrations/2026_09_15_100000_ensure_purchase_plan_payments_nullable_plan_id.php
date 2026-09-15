<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('purchase_plan_payments')) {
            try {
                Schema::table('purchase_plan_payments', function (Blueprint $table) {
                    $table->unsignedBigInteger('purchase_plan_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                if (DB::getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF');
                    Schema::table('purchase_plan_payments', function (Blueprint $table) {
                        $table->unsignedBigInteger('purchase_plan_id')->nullable()->change();
                    });
                    DB::statement('PRAGMA foreign_keys = ON');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
