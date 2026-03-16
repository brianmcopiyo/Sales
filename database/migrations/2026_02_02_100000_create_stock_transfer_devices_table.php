<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_devices', function (Blueprint $table) {
            $table->uuid('stock_transfer_id');
            $table->uuid('device_id');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->primary(['stock_transfer_id', 'device_id']);
            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_devices');
    }
};
