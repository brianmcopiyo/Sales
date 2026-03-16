<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('has_received_disbursement')->default(false)->after('status');
        });

        // Update existing devices that have received disbursements
        // Devices linked to sales that have disbursements
        DB::statement("
            UPDATE devices 
            SET has_received_disbursement = true 
            WHERE sale_id IN (
                SELECT DISTINCT sale_id 
                FROM customer_disbursements 
                WHERE sale_id IS NOT NULL
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('has_received_disbursement');
        });
    }
};
