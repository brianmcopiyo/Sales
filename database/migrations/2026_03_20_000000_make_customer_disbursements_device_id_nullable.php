<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make device_id nullable so we can set it to NULL when a disbursement is
     * rejected or a sale is cancelled, freeing the device for a new disbursement
     * while keeping the old record for bookkeeping.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customer_disbursements MODIFY device_id CHAR(36) NULL');
        } else {
            Schema::table('customer_disbursements', function ($table) {
                $table->uuid('device_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customer_disbursements MODIFY device_id CHAR(36) NOT NULL');
        } else {
            Schema::table('customer_disbursements', function ($table) {
                $table->uuid('device_id')->nullable(false)->change();
            });
        }
    }
};
