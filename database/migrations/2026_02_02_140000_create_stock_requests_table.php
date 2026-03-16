<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requesting_branch_id');
            $table->uuid('requested_from_branch_id');
            $table->uuid('product_id');
            $table->unsignedInteger('quantity_requested');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('stock_transfer_id')->nullable();
            $table->timestamps();

            $table->foreign('requesting_branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('requested_from_branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_requests');
    }
};
