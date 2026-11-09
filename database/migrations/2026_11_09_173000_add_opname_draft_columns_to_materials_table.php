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
            if (!Schema::hasColumn('materials', 'opname_draft_qty')) {
                $table->decimal('opname_draft_qty', 12, 2)->nullable()->after('stock_qty');
            }
            if (!Schema::hasColumn('materials', 'opname_draft_notes')) {
                $table->text('opname_draft_notes')->nullable()->after('opname_draft_qty');
            }
            if (!Schema::hasColumn('materials', 'opname_draft_at')) {
                $table->timestamp('opname_draft_at')->nullable()->after('opname_draft_notes');
            }
            if (!Schema::hasColumn('materials', 'opname_draft_by')) {
                $table->unsignedBigInteger('opname_draft_by')->nullable()->after('opname_draft_at');
            }
        });

        if (!Schema::hasTable('stock_opname_logs')) {
            Schema::create('stock_opname_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('material_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->decimal('system_stock', 12, 2)->default(0);
                $table->decimal('physical_stock', 12, 2)->default(0);
                $table->decimal('difference', 12, 2)->default(0);
                $table->string('status', 30)->default('draft'); // draft, applied, discarded
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('branch_id');
                $table->index('material_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_logs');
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn([
                'opname_draft_qty',
                'opname_draft_notes',
                'opname_draft_at',
                'opname_draft_by'
            ]);
        });
    }
};
