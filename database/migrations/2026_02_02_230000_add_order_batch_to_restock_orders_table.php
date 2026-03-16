<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->string('order_batch', 64)->nullable()->after('order_number')->index();
        });
    }

    public function down(): void
    {
        Schema::table('restock_orders', function (Blueprint $table) {
            $table->dropIndex(['order_batch']);
            $table->dropColumn('order_batch');
        });
    }
};
