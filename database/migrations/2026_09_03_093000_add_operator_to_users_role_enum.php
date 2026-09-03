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
        // Modify enum to add 'operator' role
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'purchasing', 'cashier', 'operator') NOT NULL DEFAULT 'cashier'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'purchasing', 'cashier') NOT NULL DEFAULT 'cashier'");
    }
};
