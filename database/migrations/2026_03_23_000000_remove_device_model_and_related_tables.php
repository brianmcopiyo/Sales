<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the device model: drop device-related tables and device_id columns from other tables.
     */
    public function up(): void
    {
        // Drop tables that reference devices (order: dependents first)
        Schema::dropIfExists('device_requests');
        Schema::dropIfExists('device_replacements');
        Schema::dropIfExists('device_status_logs');
        Schema::dropIfExists('stock_transfer_devices');

        // Drop device_id from sale_items
        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'device_id')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropForeign(['device_id']);
                $table->dropColumn('device_id');
            });
        }

        // Drop device_id from customer_disbursements (drop FK first; unique index is used by FK)
        if (Schema::hasTable('customer_disbursements') && Schema::hasColumn('customer_disbursements', 'device_id')) {
            Schema::table('customer_disbursements', function (Blueprint $table) {
                $table->dropForeign(['device_id']);
            });
            Schema::table('customer_disbursements', function (Blueprint $table) {
                $table->dropUnique(['device_id']);
            });
            Schema::table('customer_disbursements', function (Blueprint $table) {
                $table->dropColumn('device_id');
            });
        }

        // Drop device_id from tickets
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'device_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['device_id']);
                $table->dropColumn('device_id');
            });
        }

        // Drop devices table
        Schema::dropIfExists('devices');
    }

    public function down(): void
    {
        // Recreating devices and related schema is not supported; this migration is one-way.
    }
};
