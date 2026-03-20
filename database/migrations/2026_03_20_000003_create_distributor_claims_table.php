<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('distributor_profile_id')->constrained('distributor_profiles')->cascadeOnDelete();
            $table->string('claim_number')->unique();
            $table->enum('type', ['damaged_goods', 'short_shipment', 'scheme_settlement', 'other']);
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'settled'])->default('pending');
            $table->text('description');
            $table->decimal('amount_claimed', 12, 2)->nullable();
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->foreignUuid('reference_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_claims');
    }
};
