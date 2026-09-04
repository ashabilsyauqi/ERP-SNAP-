<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'has_click_charge')) {
                $table->boolean('has_click_charge')->default(false);
            }
            if (!Schema::hasColumn('materials', 'click_charge')) {
                $table->decimal('click_charge', 12, 2)->default(0);
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_details', 'click_charge')) {
                $table->decimal('click_charge', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'click_charge')) {
                $table->dropColumn('click_charge');
            }
            if (Schema::hasColumn('materials', 'has_click_charge')) {
                $table->dropColumn('has_click_charge');
            }
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_details', 'click_charge')) {
                $table->dropColumn('click_charge');
            }
        });
    }
};
