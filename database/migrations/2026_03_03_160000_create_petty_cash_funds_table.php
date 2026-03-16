<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('custodian_user_id')->nullable();
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->decimal('fund_limit', 12, 2)->default(0)->comment('Imprest amount');
            $table->string('currency', 10)->default('TSh');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('custodian_user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_funds');
    }
};
