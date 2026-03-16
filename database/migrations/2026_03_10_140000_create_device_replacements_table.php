<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_replacements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id');
            $table->uuid('original_device_id');
            $table->uuid('replacement_device_id');
            $table->text('reason')->nullable();
            $table->uuid('replaced_by')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('original_device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('replacement_device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('replaced_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_replacements');
    }
};
