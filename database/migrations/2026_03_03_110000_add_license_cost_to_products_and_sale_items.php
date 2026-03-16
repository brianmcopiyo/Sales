<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * License cost per product; applied as cost to sell when creating sales.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('license_cost', 12, 2)->nullable()->after('minimum_stock_level');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_license_cost', 12, 2)->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('license_cost');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_license_cost');
        });
    }
};
