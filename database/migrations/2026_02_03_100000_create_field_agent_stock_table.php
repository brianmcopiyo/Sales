<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_agent_stock', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('field_agent_id'); // user_id of the field agent
            $table->uuid('branch_id');
            $table->uuid('product_id');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['field_agent_id', 'branch_id', 'product_id']);
            $table->foreign('field_agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_agent_stock');
    }
};
