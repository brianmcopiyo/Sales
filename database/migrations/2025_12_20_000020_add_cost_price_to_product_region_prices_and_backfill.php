<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add cost_price to product_region_prices
        Schema::table('product_region_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('product_region_prices', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->default(0)->after('region_id');
            }
        });

        // 2) Backfill: for each product + each region, ensure a regional pricing row exists.
        // Use existing products.cost_price + products.selling_price as initial values.
        if (Schema::hasColumn('products', 'cost_price') && Schema::hasColumn('products', 'selling_price')) {
            $regionIds = DB::table('regions')->pluck('id')->all();
            if (count($regionIds) === 0) {
                return;
            }

            DB::table('products')
                ->select('id', 'cost_price', 'selling_price')
                ->orderBy('id')
                ->chunkById(200, function ($products) use ($regionIds) {
                    foreach ($products as $product) {
                        foreach ($regionIds as $regionId) {
                            $existing = DB::table('product_region_prices')
                                ->where('product_id', $product->id)
                                ->where('region_id', $regionId)
                                ->first(['id']);

                            if ($existing) {
                                DB::table('product_region_prices')
                                    ->where('id', $existing->id)
                                    ->update([
                                        'cost_price' => $product->cost_price ?? 0,
                                        'selling_price' => $product->selling_price ?? 0,
                                        'updated_at' => now(),
                                    ]);
                            } else {
                                DB::table('product_region_prices')->insert([
                                    'id' => (string) Str::uuid(),
                                    'product_id' => $product->id,
                                    'region_id' => $regionId,
                                    'cost_price' => $product->cost_price ?? 0,
                                    'selling_price' => $product->selling_price ?? 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        Schema::table('product_region_prices', function (Blueprint $table) {
            if (Schema::hasColumn('product_region_prices', 'cost_price')) {
                $table->dropColumn('cost_price');
            }
        });
    }
};


