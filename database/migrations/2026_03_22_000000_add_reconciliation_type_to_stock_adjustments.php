<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'reconciliation' to adjustment_type enum for reconciliation fix adjustments.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_adjustments MODIFY COLUMN adjustment_type ENUM('stock_take', 'manual', 'correction', 'sale', 'reconciliation') DEFAULT 'stock_take'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_adjustments MODIFY COLUMN adjustment_type ENUM('stock_take', 'manual', 'correction', 'sale') DEFAULT 'stock_take'");
        }
    }
};
