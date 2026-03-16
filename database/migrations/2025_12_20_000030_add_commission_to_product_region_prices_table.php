<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_region_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('product_region_prices', 'commission_per_device')) {
                $table->decimal('commission_per_device', 10, 2)->default(0)->after('selling_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_region_prices', function (Blueprint $table) {
            if (Schema::hasColumn('product_region_prices', 'commission_per_device')) {
                $table->dropColumn('commission_per_device');
            }
        });
    }
};


