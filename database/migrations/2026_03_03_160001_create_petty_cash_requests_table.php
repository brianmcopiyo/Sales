<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('petty_cash_fund_id');
            $table->uuid('requested_by');
            $table->decimal('amount', 12, 2);
            $table->string('category', 80)->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->uuid('disbursed_by')->nullable();
            $table->string('receipt_attachment_path')->nullable();
            $table->timestamps();

            $table->foreign('petty_cash_fund_id')->references('id')->on('petty_cash_funds')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('disbursed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_requests');
    }
};
