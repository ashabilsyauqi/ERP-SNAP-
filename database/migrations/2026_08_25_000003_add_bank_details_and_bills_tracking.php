<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add Bank details to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('alamat');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_account_number');
            }
        });

        // 2. Add payment and bill tracking to purchase_plans table
        Schema::table('purchase_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_plans', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->after('status');
            }
            if (!Schema::hasColumn('purchase_plans', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('purchase_plans', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null')->after('paid_at');
            }
            if (!Schema::hasColumn('purchase_plans', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('paid_by');
            }
            if (!Schema::hasColumn('purchase_plans', 'account_id')) {
                $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null')->after('payment_method');
            }
            if (!Schema::hasColumn('purchase_plans', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('account_id');
            }
            if (!Schema::hasColumn('purchase_plans', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('payment_reference');
            }
        });

        // 3. Add payment tracking to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->after('status');
            }
            if (!Schema::hasColumn('purchases', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('purchases', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null')->after('paid_at');
            }
            if (!Schema::hasColumn('purchases', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('paid_by');
            }
            if (!Schema::hasColumn('purchases', 'account_id')) {
                $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null')->after('payment_method');
            }
            if (!Schema::hasColumn('purchases', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['payment_status', 'paid_at', 'paid_by', 'payment_method', 'account_id', 'payment_reference']);
        });

        Schema::table('purchase_plans', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['payment_status', 'paid_at', 'paid_by', 'payment_method', 'account_id', 'payment_reference', 'payment_notes']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_account_name']);
        });
    }
};
