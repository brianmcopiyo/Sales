<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();
            $table->decimal('lat_in', 10, 8)->nullable();
            $table->decimal('lng_in', 11, 8)->nullable();
            $table->decimal('lat_out', 10, 8)->nullable();
            $table->decimal('lng_out', 11, 8)->nullable();
            $table->string('status', 32)->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'attendance_date'], 'attendances_user_date_unique');
            $table->index(['attendance_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
