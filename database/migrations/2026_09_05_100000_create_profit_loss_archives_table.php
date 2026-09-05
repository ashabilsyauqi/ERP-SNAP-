<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profit_loss_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period_type', 30)->default('monthly'); // daily, monthly, yearly, custom
            $table->string('period_label', 100);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_omzet', 15, 2)->default(0);
            $table->decimal('total_hpp', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('total_opex', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->string('pdf_filename')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_loss_archives');
    }
};
