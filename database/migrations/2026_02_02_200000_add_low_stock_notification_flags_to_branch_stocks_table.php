<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when we last sent low-stock / zero-stock alerts so we only notify once until restocked. Additive.
     */
    public function up(): void
    {
        Schema::table('branch_stocks', function (Blueprint $table) {
            $table->timestamp('low_stock_notified_at')->nullable()->after('reserved_quantity');
            $table->timestamp('zero_stock_notified_at')->nullable()->after('low_stock_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('branch_stocks', function (Blueprint $table) {
            $table->dropColumn(['low_stock_notified_at', 'zero_stock_notified_at']);
        });
    }
};
