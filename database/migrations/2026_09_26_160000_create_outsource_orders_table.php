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
        Schema::create('outsource_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name', 150);
            $table->string('customer_phone', 50)->nullable();
            
            // Customer Order Details
            $table->string('job_title', 255);
            $table->text('description')->nullable();
            $table->integer('qty')->default(1);
            $table->string('unit', 50)->default('pcs');
            $table->decimal('customer_price', 15, 2)->default(0);
            $table->string('payment_method', 50)->default('Cash'); // Cash, Transfer, QRIS
            $table->string('payment_status', 50)->default('PAID'); // PAID, PARTIAL, UNPAID
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->timestamp('receipt_printed_at')->nullable();

            // Vendor & HPP Info
            $table->string('vendor_name', 150)->nullable();
            $table->string('vendor_phone', 50)->nullable();
            $table->decimal('vendor_cost', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('estimated_margin', 15, 2)->default(0);
            $table->text('vendor_notes')->nullable();

            // Pipeline Stages & Workflow
            $table->string('status', 50)->default('draft_customer'); 
            // draft_customer, pending_approval, in_production, qc_passed, completed, rejected, cancelled

            // Approvals & QC
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('qc_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qc_at')->nullable();
            $table->text('qc_notes')->nullable();

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            // Linked official sales transaction
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outsource_orders');
    }
};
