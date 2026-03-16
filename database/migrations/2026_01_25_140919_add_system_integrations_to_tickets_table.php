<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->uuid('device_id')->nullable()->after('sale_id');
            $table->uuid('product_id')->nullable()->after('device_id');
            $table->uuid('branch_id')->nullable()->after('product_id');
            $table->uuid('field_agent_id')->nullable()->after('assigned_to');
            $table->uuid('disbursement_id')->nullable()->after('field_agent_id');
            
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('field_agent_id')->references('user_id')->on('field_agents')->onDelete('set null');
            $table->foreign('disbursement_id')->references('id')->on('customer_disbursements')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['field_agent_id']);
            $table->dropForeign(['disbursement_id']);
            
            $table->dropColumn(['device_id', 'product_id', 'branch_id', 'field_agent_id', 'disbursement_id']);
        });
    }
};
