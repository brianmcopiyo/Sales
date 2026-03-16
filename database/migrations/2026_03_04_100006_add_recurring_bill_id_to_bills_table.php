<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->uuid('recurring_bill_id')->nullable()->after('category_id');
            $table->foreign('recurring_bill_id')->references('id')->on('recurring_bills')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['recurring_bill_id']);
            $table->dropColumn('recurring_bill_id');
        });
    }
};
