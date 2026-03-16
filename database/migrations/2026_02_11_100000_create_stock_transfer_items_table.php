<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safe for production: creates items table and backfills existing transfers
     * so every current and previous transfer has at least one item (1:1 for legacy).
     */
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_transfer_id');
            $table->uuid('product_id');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('quantity_received')->nullable();
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Backfill: one item per existing transfer (keeps current and previous transfers working)
        $transfers = DB::table('stock_transfers')->select('id', 'product_id', 'quantity', 'quantity_received')->get();
        foreach ($transfers as $t) {
            DB::table('stock_transfer_items')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'stock_transfer_id' => $t->id,
                'product_id' => $t->product_id,
                'quantity' => $t->quantity,
                'quantity_received' => $t->quantity_received,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
