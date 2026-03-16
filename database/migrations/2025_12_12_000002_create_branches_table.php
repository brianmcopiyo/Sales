<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->uuid('head_branch_id')->nullable();
            $table->uuid('region_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('head_branch_id')->references('id')->on('branches')->onDelete('cascade');
            // Note: region_id foreign key will be added in migration 000018
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

