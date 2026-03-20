<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_scheme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('scheme_id')->constrained('schemes')->cascadeOnDelete();
            $table->decimal('discount_applied', 10, 2);
            $table->timestamps();

            $table->index(['sale_id', 'scheme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_scheme');
    }
};
