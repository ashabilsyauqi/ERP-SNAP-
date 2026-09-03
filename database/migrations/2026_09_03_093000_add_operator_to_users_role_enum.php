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
        // Modify enum to add 'operator' role only if driver is MySQL
        // SQLite stores enum as string/text without native ENUM or MODIFY COLUMN syntax
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'purchasing', 'cashier', 'operator') NOT NULL DEFAULT 'cashier'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'purchasing', 'cashier') NOT NULL DEFAULT 'cashier'");
        }
    }
};
