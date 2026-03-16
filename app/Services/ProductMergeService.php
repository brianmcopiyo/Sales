<?php

namespace App\Services;

use App\Models\AgentStockRequest;
use App\Models\BranchStock;
use App\Models\Device;
use App\Models\FieldAgentStock;
use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductRegionPrice;
use App\Models\RestockOrder;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockRequest;
use App\Models\StockTakeItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ProductMergeService
{
    /**
     * Merge one or more source products into a target (recipient) product.
     * Transfers all devices, sales, branch stock, and related data to the target, then deletes the source products.
     *
     * @param array<string> $sourceIds UUIDs of products to merge from (will be removed)
     * @param string $targetId UUID of the product to keep and receive all data
     * @return array{devices: int, sale_items: int, branch_stocks_merged: int, messages: string[]}
     */
    public function merge(array $sourceIds, string $targetId): array
    {
        $sourceIds = array_values(array_unique($sourceIds));
        if (in_array($targetId, $sourceIds, true)) {
            throw new \InvalidArgumentException('Target product cannot be in the source list.');
        }

        $target = Product::findOrFail($targetId);
        $sources = Product::whereIn('id', $sourceIds)->get();
        if ($sources->count() !== count($sourceIds)) {
            throw new \InvalidArgumentException('One or more source products not found.');
        }

        $stats = ['devices' => 0, 'sale_items' => 0, 'branch_stocks_merged' => 0, 'messages' => []];

        DB::transaction(function () use ($sourceIds, $targetId, &$stats) {
            // 1. Reassign devices to target
            $stats['devices'] = Device::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 2. Reassign sale items (sales) to target
            $stats['sale_items'] = SaleItem::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 3. Merge branch stocks: per (branch_id) sum quantity and reserved_quantity into target row
            $branchStocks = BranchStock::whereIn('product_id', array_merge($sourceIds, [$targetId]))->get();
            $byBranch = $branchStocks->groupBy('branch_id');
            foreach ($byBranch as $branchId => $rows) {
                $targetRow = $rows->firstWhere('product_id', $targetId);
                $sourceRows = $rows->whereIn('product_id', $sourceIds);
                $totalQty = $rows->sum('quantity');
                $totalReserved = $rows->sum('reserved_quantity');
                if ($targetRow) {
                    $targetRow->update(['quantity' => $totalQty, 'reserved_quantity' => $totalReserved]);
                } else {
                    BranchStock::create([
                        'branch_id' => $branchId,
                        'product_id' => $targetId,
                        'quantity' => $totalQty,
                        'reserved_quantity' => $totalReserved,
                    ]);
                }
                $stats['branch_stocks_merged'] += $sourceRows->count();
            }
            BranchStock::whereIn('product_id', $sourceIds)->delete();

            // 4. Reassign stock transfers and their line items
            StockTransfer::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);
            StockTransferItem::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 5. Region prices: copy missing region prices from sources to target, then remove source rows
            $targetRegionIds = ProductRegionPrice::where('product_id', $targetId)->pluck('region_id')->all();
            foreach ($sourceIds as $sid) {
                $sourcePrices = ProductRegionPrice::where('product_id', $sid)->get();
                foreach ($sourcePrices as $pr) {
                    if (!in_array($pr->region_id, $targetRegionIds, true)) {
                        ProductRegionPrice::create([
                            'product_id' => $targetId,
                            'region_id' => $pr->region_id,
                            'cost_price' => $pr->cost_price,
                            'selling_price' => $pr->selling_price,
                            'commission_per_device' => $pr->commission_per_device,
                        ]);
                        $targetRegionIds[] = $pr->region_id;
                    }
                }
            }
            ProductRegionPrice::whereIn('product_id', $sourceIds)->delete();

            // 6. Restock orders
            RestockOrder::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 7. Inventory movements & alerts
            InventoryMovement::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);
            InventoryAlert::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);
            StockAdjustment::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 8. Stock take items: per (stock_take_id) merge source + existing target into one row to avoid unique violation
            $this->mergeStockTakeItemsForProductMerge($sourceIds, $targetId);

            // 9. Field agent stock: reassign then merge duplicates (same field_agent_id, branch_id, product_id)
            FieldAgentStock::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);
            $this->mergeFieldAgentStockDuplicates($targetId);

            // 10. Stock requests & agent stock requests
            StockRequest::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);
            AgentStockRequest::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 11. Tickets (product_id nullable)
            Ticket::whereIn('product_id', $sourceIds)->update(['product_id' => $targetId]);

            // 12. Delete source products (and their images)
            $sourcesToDelete = Product::whereIn('id', $sourceIds)->get();
            foreach ($sourcesToDelete as $p) {
                if ($p->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($p->image)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($p->image);
                }
                $p->delete();
            }
        });

        $stats['messages'][] = "Merged " . count($sourceIds) . " product(s) into {$target->name}. Transferred: {$stats['devices']} devices, {$stats['sale_items']} sale line items.";

        return $stats;
    }

    /**
     * Merge stock_take_items when merging products: for each stock_take that has source product rows
     * (and possibly an existing target row), keep one row with product_id=targetId and aggregated quantities.
     * Avoids unique (stock_take_id, product_id) violation from bulk update.
     */
    private function mergeStockTakeItemsForProductMerge(array $sourceIds, string $targetId): void
    {
        $stockTakeIds = StockTakeItem::whereIn('product_id', $sourceIds)
            ->distinct()
            ->pluck('stock_take_id');

        foreach ($stockTakeIds as $stockTakeId) {
            $items = StockTakeItem::where('stock_take_id', $stockTakeId)
                ->where(function ($q) use ($sourceIds, $targetId) {
                    $q->whereIn('product_id', $sourceIds)->orWhere('product_id', $targetId);
                })
                ->orderByRaw('product_id = ? DESC', [$targetId]) // prefer existing target row
                ->orderBy('created_at')
                ->get();

            if ($items->isEmpty()) {
                continue;
            }

            $keep = $items->first();
            $aggregated = [
                'product_id' => $targetId,
                'system_quantity' => $items->sum('system_quantity'),
                'physical_quantity' => $items->max('physical_quantity') ?? $items->sum('physical_quantity'),
                'variance' => $items->sum('variance'),
                'first_count' => $keep->first_count,
                'recount' => $keep->recount,
                'notes' => $keep->notes,
                'counted_by' => $keep->counted_by,
                'counted_at' => $keep->counted_at,
            ];

            $keep->update($aggregated);
            $items->skip(1)->each->delete();
        }
    }

    /**
     * After reassigning product_id to target, merge duplicate field_agent_stock rows (same field_agent_id, branch_id, product_id).
     */
    private function mergeFieldAgentStockDuplicates(string $targetId): void
    {
        $dupes = FieldAgentStock::where('product_id', $targetId)
            ->select('field_agent_id', 'branch_id')
            ->groupBy('field_agent_id', 'branch_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($dupes as $row) {
            $items = FieldAgentStock::where('field_agent_id', $row->field_agent_id)
                ->where('branch_id', $row->branch_id)
                ->where('product_id', $targetId)
                ->orderBy('created_at')
                ->get();
            $first = $items->first();
            $first->update(['quantity' => $items->sum('quantity')]);
            $items->skip(1)->each->delete();
        }
    }
}
