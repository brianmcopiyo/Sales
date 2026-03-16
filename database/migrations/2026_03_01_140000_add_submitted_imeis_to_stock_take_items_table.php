<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_take_items', function (Blueprint $table) {
            $table->json('submitted_imeis')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('stock_take_items', function (Blueprint $table) {
            $table->dropColumn('submitted_imeis');
        });
    }
};
