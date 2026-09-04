<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('transactions')
            ->where('order_status', 'draft')
            ->update([
                'remaining_amount' => 0,
                'paid_amount' => 0,
                'payment_status' => 'UNPAID'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as draft transactions do not carry receivables
    }
};
