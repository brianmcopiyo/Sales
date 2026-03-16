<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename supplier_name to dealership_name and add optional dealership_id to restock_orders. Safe for production:
     * - Column is nullable; existing rows keep data via renamed dealership_name.
     */
    public function up(): void
    {
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->renameColumn('supplier_name', 'dealership_name');
        });
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->foreignUuid('dealership_id')->nullable()->after('dealership_name')->constrained('dealerships')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->dropForeign(['dealership_id']);
        });
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->renameColumn('dealership_name', 'supplier_name');
        });
    }
};
