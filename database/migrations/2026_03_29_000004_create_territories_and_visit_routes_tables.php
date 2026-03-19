<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignUuid('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visit_routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('route_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visit_route_outlet', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visit_route_id')->constrained('visit_routes')->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->timestamps();
        });

        Schema::table('visit_route_outlet', function (Blueprint $table) {
            $table->unique(['visit_route_id', 'outlet_id'], 'visit_route_outlet_unique');
            $table->index(['visit_route_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_route_outlet');
        Schema::dropIfExists('visit_routes');
        Schema::dropIfExists('territories');
    }
};
