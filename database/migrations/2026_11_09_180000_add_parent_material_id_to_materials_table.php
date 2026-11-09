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
            $table->unsignedBigInteger('parent_material_id')->nullable()->after('branch_id')->index();
            $table->unsignedTinyInteger('print_sides')->default(1)->after('parent_material_id');

            // SQLite supports foreign key definitions if enabled, or simple indexed column
            $table->foreign('parent_material_id')->references('id')->on('materials')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['parent_material_id']);
            $table->dropColumn(['parent_material_id', 'print_sides']);
        });
    }
};
