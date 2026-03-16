<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Change users.role from ENUM to string so any role slug from the roles table
     * can be stored without "Data truncated" errors. ENUM is rigid and requires
     * schema changes to add new values.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE users MODIFY role VARCHAR(64) NOT NULL DEFAULT 'customer'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'head_branch_manager', 'regional_branch_manager', 'staff', 'customer') NOT NULL DEFAULT 'customer'");
    }
};
