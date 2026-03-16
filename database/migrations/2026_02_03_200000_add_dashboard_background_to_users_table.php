<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dashboard_background_type', 20)->nullable()->after('phone');
            $table->string('dashboard_background_value', 255)->nullable()->after('dashboard_background_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dashboard_background_type', 'dashboard_background_value']);
        });
    }
};
