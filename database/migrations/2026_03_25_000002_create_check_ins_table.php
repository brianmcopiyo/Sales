<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('lat_in', 10, 8);
            $table->decimal('lng_in', 11, 8);
            $table->decimal('lat_out', 10, 8)->nullable();
            $table->decimal('lng_out', 11, 8)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->index(['user_id', 'check_in_at']);
            $table->index(['outlet_id', 'check_in_at']);
            $table->index('check_in_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
