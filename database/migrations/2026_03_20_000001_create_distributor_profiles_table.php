<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('assigned_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('portal_enabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_profiles');
    }
};
