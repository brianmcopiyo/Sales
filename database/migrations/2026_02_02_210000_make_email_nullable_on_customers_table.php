<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure customers.email accepts NULL so email is optional. Additive – no data loss.
     * Safe to run even if the column is already nullable.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE customers ALTER COLUMN email DROP NOT NULL');
        }
        // SQLite: original create_customers migration already uses nullable(); no change needed.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE customers ALTER COLUMN email SET NOT NULL');
        }
    }
};
