<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('distributor_profile_id')->constrained('distributor_profiles')->cascadeOnDelete();
            $table->enum('target_type', ['revenue', 'quantity', 'outlet_coverage']);
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
            $table->smallInteger('period_year');
            $table->tinyInteger('period_value'); // month 1-12 or quarter 1-4; 1 for yearly
            $table->decimal('target_value', 14, 2);
            $table->foreignUuid('set_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_targets');
    }
};
