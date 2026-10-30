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
        Schema::create('boss_cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->default(1)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('withdrawal_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('taken_by', 100);
            $table->string('purpose', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('daily_closing_report_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boss_cash_withdrawals');
    }
};
