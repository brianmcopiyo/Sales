<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * License cost (cost to sell) per sale for revenue/profit reporting.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_license_cost', 12, 2)->default(0)->after('total');
        });

        // Backfill from sale_items
        $sales = DB::table('sales')->pluck('id');
        foreach ($sales as $saleId) {
            $sum = DB::table('sale_items')
                ->where('sale_id', $saleId)
                ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_license_cost, 0)), 0) as total')
                ->value('total');
            DB::table('sales')->where('id', $saleId)->update(['total_license_cost' => $sum ?? 0]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('total_license_cost');
        });
    }
};
