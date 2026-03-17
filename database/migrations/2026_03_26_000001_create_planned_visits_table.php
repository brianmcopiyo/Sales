<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planned_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->date('planned_date');
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('planned_visits', function (Blueprint $table) {
            $table->index(['user_id', 'planned_date']);
            $table->index(['planned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planned_visits');
    }
};
