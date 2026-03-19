<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->date('expense_date');
            $table->string('category', 64);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('KES');
            $table->string('status', 32)->default('submitted');
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });

        Schema::table('field_expenses', function (Blueprint $table) {
            $table->index(['user_id', 'expense_date']);
            $table->index(['status', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_expenses');
    }
};
