<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration ensures stock records exist and properly reflect existing devices.
     * Devices represent available inventory stock, so we increase stock by device count.
     * After processing, we mark devices as counted to prevent re-entry.
     */
    public function up(): void
    {
        // Only proceed if both tables exist
        if (!Schema::hasTable('devices') || !Schema::hasTable('branch_stocks')) {
            return;
        }

        // Ensure stock_counted column exists (add it if migration hasn't run yet)
        $columnExists = Schema::hasColumn('devices', 'stock_counted');
        if (!$columnExists) {
            Schema::table('devices', function (Blueprint $table) {
                $table->boolean('stock_counted')->default(false)->after('has_received_disbursement');
            });
        }

        // Get a system user ID for created_by (prefer admin, otherwise first user)
        $systemUserId = null;
        if (Schema::hasTable('users')) {
            // Try to get an admin user first (users have role_id column)
            if (Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
                $adminRole = DB::table('roles')
                    ->where('slug', 'admin')
                    ->select('id')
                    ->first();

                if ($adminRole) {
                    $adminUser = DB::table('users')
                        ->where('role_id', $adminRole->id)
                        ->select('id')
                        ->first();

                    if ($adminUser) {
                        $systemUserId = $adminUser->id;
                    }
                }
            }

            // Fallback to first user if no admin found
            if (!$systemUserId) {
                $firstUser = DB::table('users')->select('id')->first();
                if ($firstUser) {
                    $systemUserId = $firstUser->id;
                }
            }
        }

        $canCreateMovements = $systemUserId !== null && Schema::hasTable('inventory_movements');

        // Build query for non-sold devices that haven't been counted yet
        $deviceQuery = DB::table('devices')
            ->where('status', '!=', 'sold')
            ->whereNotNull('branch_id')
            ->whereNotNull('product_id');

        // Only filter by stock_counted if column exists
        if ($columnExists) {
            $deviceQuery->where('stock_counted', false);
        }

        // Get device counts grouped by branch and product
        $deviceCounts = $deviceQuery->select(
            'branch_id',
            'product_id',
            DB::raw('COUNT(*) as device_count')
        )
            ->groupBy('branch_id', 'product_id')
            ->get();

        // Also get device IDs to mark as counted
        $devicesToMarkQuery = DB::table('devices')
            ->where('status', '!=', 'sold')
            ->whereNotNull('branch_id')
            ->whereNotNull('product_id');

        if ($columnExists) {
            $devicesToMarkQuery->where('stock_counted', false);
        }

        $devicesToMark = $devicesToMarkQuery->pluck('id')->toArray();

        foreach ($deviceCounts as $deviceCount) {
            $branchId = $deviceCount->branch_id;
            $productId = $deviceCount->product_id;
            $count = (int) $deviceCount->device_count;

            // Get or create branch stock record
            $branchStock = DB::table('branch_stocks')
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            if ($branchStock) {
                // Stock record exists - increase by device count
                $currentQuantity = (int) $branchStock->quantity;
                $quantityBefore = $currentQuantity;

                // Increase stock by device count (devices represent available inventory)
                $newQuantity = $currentQuantity + $count;
                $quantityChange = $count; // Positive change

                // Update branch stock
                DB::table('branch_stocks')
                    ->where('branch_id', $branchId)
                    ->where('product_id', $productId)
                    ->update([
                        'quantity' => $newQuantity,
                        'updated_at' => now(),
                    ]);

                // Record inventory movement
                if ($canCreateMovements) {
                    DB::table('inventory_movements')->insert([
                        'id' => (string) Str::uuid(),
                        'movement_number' => 'MOV-' . strtoupper(uniqid()),
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'movement_type' => 'adjustment',
                        'quantity' => $quantityChange,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $newQuantity,
                        'reference_type' => null,
                        'reference_id' => null,
                        'reason' => "Stock adjustment for {$count} existing device(s) (excluding sold devices)",
                        'notes' => "Migration: fix_stock_levels_for_existing_devices - Increased stock from {$quantityBefore} to {$newQuantity} to account for {$count} existing devices",
                        'created_by' => $systemUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Stock record doesn't exist - create it with quantity equal to device count
                // (devices represent available inventory stock)
                $insertData = [
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => $count,
                    'reserved_quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add notification columns if they exist
                if (Schema::hasColumn('branch_stocks', 'low_stock_notified_at')) {
                    $insertData['low_stock_notified_at'] = null;
                }
                if (Schema::hasColumn('branch_stocks', 'zero_stock_notified_at')) {
                    $insertData['zero_stock_notified_at'] = null;
                }

                DB::table('branch_stocks')->insert($insertData);

                // Record inventory movement
                if ($canCreateMovements) {
                    DB::table('inventory_movements')->insert([
                        'id' => (string) Str::uuid(),
                        'movement_number' => 'MOV-' . strtoupper(uniqid()),
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'movement_type' => 'adjustment',
                        'quantity' => $count,
                        'quantity_before' => 0,
                        'quantity_after' => $count,
                        'reference_type' => null,
                        'reference_id' => null,
                        'reason' => "Stock adjustment for {$count} existing device(s) (excluding sold devices)",
                        'notes' => "Migration: fix_stock_levels_for_existing_devices - Created stock record with quantity {$count} to reflect {$count} existing non-sold device(s)",
                        'created_by' => $systemUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Mark all processed devices as counted to prevent re-entry
        if (!empty($devicesToMark)) {
            DB::table('devices')
                ->whereIn('id', $devicesToMark)
                ->update([
                    'stock_counted' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration creates/updates stock records, so rollback would be complex
        // We'll leave it as-is for safety
    }
};
