<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->decimal('total_acquisition_cost', 14, 2)->nullable()->after('quantity_received');
        });
    }

    public function down(): void
    {
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->dropColumn('total_acquisition_cost');
        });
    }
};
