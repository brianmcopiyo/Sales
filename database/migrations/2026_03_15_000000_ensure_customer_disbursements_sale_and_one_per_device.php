<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Safe migration: (1) backfill sale_id from device, (2) deduplicate by device_id keeping one per device,
     * (3) add unique index so one device can have at most one disbursement.
     * Application is live: no destructive changes without deduplication first.
     */
    public function up(): void
    {
        // Step 1: Backfill sale_id where missing (from device.sale_id)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("
                UPDATE customer_disbursements cd
                INNER JOIN devices d ON d.id = cd.device_id AND d.sale_id IS NOT NULL
                SET cd.sale_id = d.sale_id
                WHERE cd.sale_id IS NULL
            ");
        } else {
            $rows = DB::table('customer_disbursements')
                ->whereNull('sale_id')
                ->whereNotNull('device_id')
                ->get(['id', 'device_id']);
            foreach ($rows as $row) {
                $saleId = DB::table('devices')->where('id', $row->device_id)->value('sale_id');
                if ($saleId !== null) {
                    DB::table('customer_disbursements')->where('id', $row->id)->update(['sale_id' => $saleId]);
                }
            }
        }

        // Step 2: Deduplicate by device_id – keep one disbursement per device (prefer approved, then pending, then rejected; then oldest id)
        $statusOrder = [
            'approved' => 1,
            'pending' => 2,
            'rejected' => 3,
        ];
        $duplicateDeviceIds = DB::table('customer_disbursements')
            ->whereNotNull('device_id')
            ->select('device_id')
            ->groupBy('device_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('device_id');

        foreach ($duplicateDeviceIds as $deviceId) {
            $candidates = DB::table('customer_disbursements')
                ->where('device_id', $deviceId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'status']);

            // Prefer approved, then pending, then rejected
            $keeper = $candidates->first(function ($c) {
                return $c->status === 'approved';
            }) ?? $candidates->first(function ($c) {
                return $c->status === 'pending';
            }) ?? $candidates->first(function ($c) {
                return $c->status === 'rejected';
            }) ?? $candidates->first();

            $keeperId = $keeper->id;
            $toDeleteIds = $candidates->skip(1)->pluck('id')->all();

            if (empty($toDeleteIds)) {
                continue;
            }

            // Point any tickets that referenced a deleted disbursement to the keeper
            if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'disbursement_id')) {
                DB::table('tickets')
                    ->whereIn('disbursement_id', $toDeleteIds)
                    ->update(['disbursement_id' => $keeperId]);
            }

            DB::table('customer_disbursements')->whereIn('id', $toDeleteIds)->delete();
        }

        // Step 3: Add unique index on device_id (one disbursement per device; multiple NULLs allowed in MySQL)
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->unique('device_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('customer_disbursements', function (Blueprint $table) {
            $table->dropUnique(['device_id']);
        });
    }
};
