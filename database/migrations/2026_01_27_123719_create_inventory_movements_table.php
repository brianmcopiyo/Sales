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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('movement_number')->unique();
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->enum('movement_type', ['receipt', 'issue', 'transfer', 'adjustment', 'sale', 'return', 'stock_take'])->default('adjustment');
            $table->integer('quantity'); // Positive for increases, negative for decreases
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->string('reference_type')->nullable(); // StockTransfer, StockAdjustment, Sale, StockTake, etc.
            $table->uuid('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['branch_id', 'product_id']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
