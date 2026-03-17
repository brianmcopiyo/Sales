<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('type')->nullable(); // retail, kiosk, dealer, etc.
            $table->text('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUuid('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('geo_fence_type')->nullable(); // null, radius, polygon
            $table->unsignedInteger('geo_fence_radius_metres')->nullable();
            $table->json('geo_fence_polygon')->nullable(); // array of {lat, lng}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->index(['branch_id', 'is_active']);
            $table->index(['assigned_to', 'is_active']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
