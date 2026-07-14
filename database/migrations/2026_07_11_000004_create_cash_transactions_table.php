<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipe', ['masuk', 'keluar']);
            $table->string('nomor_referensi', 50)->unique();
            $table->date('tanggal');
            $table->bigInteger('jumlah');
            $table->text('keterangan');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cash_transactions');
    }
};
