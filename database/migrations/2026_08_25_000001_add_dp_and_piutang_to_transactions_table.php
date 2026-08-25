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
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('payment_status', 20)->default('PAID')->after('payment_method'); // PAID, PARTIAL, UNPAID
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_status');
            $table->decimal('remaining_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('order_status', 30)->default('completed')->after('remaining_amount'); // in_production, ready, completed, cancelled
            $table->date('due_date')->nullable()->after('order_status');
            $table->text('production_notes')->nullable()->after('due_date');
        });

        // Populate existing records so paid_amount = total_price and remaining_amount = 0
        \Illuminate\Support\Facades\DB::statement("UPDATE transactions SET paid_amount = total_price, remaining_amount = 0, payment_status = 'PAID', order_status = 'completed' WHERE paid_amount = 0");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'payment_status',
                'paid_amount',
                'remaining_amount',
                'order_status',
                'due_date',
                'production_notes',
            ]);
        });
    }
};
