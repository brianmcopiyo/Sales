<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_stock_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('field_agent_id'); // user_id of the field agent requesting
            $table->uuid('branch_id'); // agent's branch (only source they can request from)
            $table->uuid('product_id');
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_fulfilled')->default(0);
            $table->string('status', 30)->default('pending'); // pending, partially_fulfilled, approved, rejected
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->text('closed_reason')->nullable();
            $table->timestamps();

            $table->foreign('field_agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_stock_requests');
    }
};
