<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_replenishments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('petty_cash_fund_id');
            $table->decimal('amount', 12, 2);
            $table->uuid('replenished_by');
            $table->text('notes')->nullable();
            $table->string('reference', 255)->nullable();
            $table->timestamps();

            $table->foreign('petty_cash_fund_id')->references('id')->on('petty_cash_funds')->onDelete('cascade');
            $table->foreign('replenished_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_replenishments');
    }
};
