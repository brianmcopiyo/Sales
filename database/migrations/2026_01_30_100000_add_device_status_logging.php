<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->uuid('sold_by_user_id')->nullable()->after('status');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->foreign('sold_by_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('device_status_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_id');
            $table->string('status', 20); // available, assigned, sold
            $table->uuid('performed_by_user_id');
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('performed_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['sold_by_user_id']);
            $table->dropColumn('sold_by_user_id');
        });

        Schema::dropIfExists('device_status_logs');
    }
};
