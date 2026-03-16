<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove branch_id from customer_disbursements. Branch for display is derived from
     * the requesting user (disbursed_by) so no DB link is needed. Safe for live: drop FK then column.
     */
    public function up(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->after('sale_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }
};
