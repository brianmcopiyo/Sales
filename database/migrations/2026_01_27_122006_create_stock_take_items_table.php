<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_take_id');
            $table->uuid('product_id');
            $table->integer('system_quantity')->default(0);
            $table->integer('physical_quantity')->nullable();
            $table->integer('variance')->default(0); // physical - system
            $table->integer('first_count')->nullable();
            $table->integer('recount')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->foreign('stock_take_id')->references('id')->on('stock_takes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('counted_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['stock_take_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
};
