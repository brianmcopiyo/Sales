<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->after('sale_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });

        // Backfill: from sale when present, else from device
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("
                UPDATE customer_disbursements cd
                LEFT JOIN sales s ON s.id = cd.sale_id
                LEFT JOIN devices d ON d.id = cd.device_id
                SET cd.branch_id = COALESCE(s.branch_id, d.branch_id)
            ");
        } else {
            $rows = DB::table('customer_disbursements')->get(['id', 'sale_id', 'device_id']);
            foreach ($rows as $row) {
                $branchId = null;
                if ($row->sale_id) {
                    $branchId = DB::table('sales')->where('id', $row->sale_id)->value('branch_id');
                }
                if ($branchId === null && $row->device_id) {
                    $branchId = DB::table('devices')->where('id', $row->device_id)->value('branch_id');
                }
                if ($branchId !== null) {
                    DB::table('customer_disbursements')->where('id', $row->id)->update(['branch_id' => $branchId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
