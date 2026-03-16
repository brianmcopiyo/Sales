<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->uuid('petty_cash_category_id')->nullable()->after('amount');
            $table->foreign('petty_cash_category_id')->references('id')->on('petty_cash_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->dropForeign(['petty_cash_category_id']);
            $table->dropColumn('petty_cash_category_id');
        });
    }
};
