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
        Schema::create('purchase_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_number')->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->date('target_date')->nullable();
            $table->decimal('total_estimated_cost', 15, 2)->default(0);
            $table->enum('status', ['draft', 'waiting_owner_approval', 'approved_by_owner', 'rejected_by_owner', 'completed'])->default('waiting_owner_approval');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_plan_id')->constrained('purchase_plans')->onDelete('cascade');
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('material_name');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->decimal('fixed_size', 8, 2)->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('estimated_unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('retail_price', 15, 2)->nullable();
            $table->json('wholesale_prices')->nullable();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('purchase_plan_id')->nullable()->after('supplier_id')->constrained('purchase_plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['purchase_plan_id']);
            $table->dropColumn('purchase_plan_id');
        });

        Schema::dropIfExists('purchase_plan_items');
        Schema::dropIfExists('purchase_plans');
    }
};
