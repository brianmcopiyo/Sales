<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconcile completed sales with no or under-credited commission.
     * 1) Backfill sale_items that have 0 commission from product_region_prices (product + branch region).
     * 2) Add commission_credited_amount on sales to track how much was credited (enables safe top-up).
     * 3) For each completed sale, credit the seller for (sum(commission_amount) - commission_credited_amount).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'commission_credited_amount')) {
                $table->decimal('commission_credited_amount', 12, 2)->default(0)->after('commission_credited_at');
            }
        });

        $salesTable = 'sales';
        $saleItemsTable = 'sale_items';
        $branchesTable = 'branches';
        $productRegionPricesTable = 'product_region_prices';
        $usersTable = 'users';

        // 1) Backfill sale_items: set commission_per_device and commission_amount from product_region_prices where currently zero
        $itemsWithZeroCommission = DB::table($saleItemsTable)
            ->join($salesTable, $saleItemsTable . '.sale_id', '=', $salesTable . '.id')
            ->leftJoin($branchesTable, $salesTable . '.branch_id', '=', $branchesTable . '.id')
            ->where(function ($q) use ($saleItemsTable) {
                $q->where($saleItemsTable . '.commission_amount', '<=', 0)
                    ->orWhereNull($saleItemsTable . '.commission_amount');
            })
            ->select(
                $saleItemsTable . '.id as item_id',
                $saleItemsTable . '.sale_id',
                $saleItemsTable . '.product_id',
                $saleItemsTable . '.quantity',
                $branchesTable . '.region_id as branch_region_id'
            )
            ->get();

        foreach ($itemsWithZeroCommission as $row) {
            $regionId = $row->branch_region_id;
            if (!$regionId) {
                $regionId = DB::table($productRegionPricesTable)
                    ->where('product_id', $row->product_id)
                    ->value('region_id');
            }
            $commissionPerDevice = 0;
            if ($regionId) {
                $commissionPerDevice = (float) (DB::table($productRegionPricesTable)
                    ->where('product_id', $row->product_id)
                    ->where('region_id', $regionId)
                    ->value('commission_per_device') ?? 0);
            }
            $quantity = (int) ($row->quantity ?? 1);
            $commissionAmount = $commissionPerDevice * $quantity;
            DB::table($saleItemsTable)->where('id', $row->item_id)->update([
                'commission_per_device' => $commissionPerDevice,
                'commission_amount' => $commissionAmount,
            ]);
        }

        // 2) For completed sales that already have commission_credited_at set, set commission_credited_amount to current sum
        // so we don't double-credit them in step 3 (we assume they were fully credited when commission_credited_at was set).
        $alreadyCredited = DB::table($salesTable)
            ->where('status', 'completed')
            ->whereNotNull('commission_credited_at')
            ->get(['id']);
        foreach ($alreadyCredited as $s) {
            $sum = (float) DB::table($saleItemsTable)->where('sale_id', $s->id)->sum('commission_amount');
            DB::table($salesTable)->where('id', $s->id)->update(['commission_credited_amount' => $sum]);
        }

        // 3) For each completed sale: credit seller for (total - already credited)
        $completed = DB::table($salesTable)
            ->where('status', 'completed')
            ->whereNotNull('sold_by')
            ->get(['id', 'sold_by']);

        foreach ($completed as $sale) {
            $total = (float) DB::table($saleItemsTable)->where('sale_id', $sale->id)->sum('commission_amount');
            $credited = (float) (DB::table($salesTable)->where('id', $sale->id)->value('commission_credited_amount') ?? 0);
            $toCredit = $total - $credited;
            if ($toCredit <= 0) {
                continue;
            }
            $user = DB::table($usersTable)->where('id', $sale->sold_by)->first(['total_commission_earned', 'commission_available_balance']);
            if (!$user) {
                continue;
            }
            DB::table($usersTable)->where('id', $sale->sold_by)->update([
                'total_commission_earned' => (float) ($user->total_commission_earned ?? 0) + $toCredit,
                'commission_available_balance' => (float) ($user->commission_available_balance ?? 0) + $toCredit,
            ]);
            DB::table($salesTable)->where('id', $sale->id)->update([
                'commission_credited_amount' => $total,
                'commission_credited_at' => DB::raw('COALESCE(commission_credited_at, NOW())'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'commission_credited_amount')) {
                $table->dropColumn('commission_credited_amount');
            }
        });
    }
};
