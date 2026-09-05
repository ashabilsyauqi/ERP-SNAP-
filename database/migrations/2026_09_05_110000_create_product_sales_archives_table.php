<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_sales_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('period_label', 100);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_items_sold', 15, 2)->default(0);
            $table->decimal('total_omzet', 15, 2)->default(0);
            $table->decimal('total_material_cost', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->string('pdf_filename')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales_archives');
    }
};
