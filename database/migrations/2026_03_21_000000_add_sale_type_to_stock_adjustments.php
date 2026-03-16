<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'sale' to adjustment_type enum so sales can create stock adjustment records.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_adjustments MODIFY COLUMN adjustment_type ENUM('stock_take', 'manual', 'correction', 'sale') DEFAULT 'stock_take'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_adjustments MODIFY COLUMN adjustment_type ENUM('stock_take', 'manual', 'correction') DEFAULT 'stock_take'");
        }
    }
};
