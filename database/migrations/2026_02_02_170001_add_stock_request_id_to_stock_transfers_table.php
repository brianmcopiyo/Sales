<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link transfers to requests for partial fulfillments (one request → many transfers). Additive.
     */
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->uuid('stock_request_id')->nullable()->after('restock_order_id');
            $table->foreign('stock_request_id')->references('id')->on('stock_requests')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['stock_request_id']);
            $table->dropColumn('stock_request_id');
        });
    }
};
