<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->string('frequency', 30); // monthly, quarterly, yearly
            $table->date('next_due_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('bill_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_bills');
    }
};
