<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Device;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\InventoryMovementService;
use Carbon\Carbon;

class StockReconciliationService
{
    /**
     * Run reconciliation and return data for report (no fix/export).
     *
     * @param  string|null  $branchIdOrCode  Branch ID, code, or name (optional)
     * @param  string|null  $productIdOrSku  Product ID or SKU (optional)
     * @param  Carbon|null  $filterDate  Today, yesterday, or null for all
     * @param  bool  $showOk  Include branch/product rows with no discrepancies
     * @param  bool  $stepsOnly  Include all steps (when filtering by date) not only discrepancy rows
     */
    public function run(
        ?string $branchIdOrCode = null,
        ?string $productIdOrSku = null,
        ?Carbon $filterDate = null,
        bool $showOk = false,
        bool $stepsOnly = true
    ): array {
        $branchQuery = Branch::query();
        if ($branchIdOrCode) {
            $branchQuery->where(function ($q) use ($branchIdOrCode) {
                $q->where('id', $branchIdOrCode)
                    ->orWhereRaw('LOWER(code) = ?', [strtolower($branchIdOrCode)])
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($branchIdOrCode)]);
            });
        }
        $branches = $branchQuery->get();
        if ($branches->isEmpty()) {
            return [
                'step_rows' => [],
                'rows' => [],
                'all_discrepancies' => [],
                'filter_date' => $filterDate,
            ];
        }

        $productQuery = Product::query();
        if ($productIdOrSku) {
            $productQuery->where('id', $productIdOrSku)
                ->orWhere('sku', $productIdOrSku);
        }
        $products = $productQuery->get()->keyBy('id');
        if ($products->isEmpty()) {
            return [
                'step_rows' => [],
                'rows' => [],
                'all_discrepancies' => [],
                'filter_date' => $filterDate,
            ];
        }

        $branchIds = $branches->pluck('id')->all();
        $productIds = $products->pluck('id')->all();

        $movementPairsQuery = InventoryMovement::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds);
        if ($filterDate !== null) {
            $movementPairsQuery->whereDate('created_at', $filterDate);
        }
        $movementPairs = $movementPairsQuery->select('branch_id', 'product_id')
            ->distinct()
            ->get()
            ->map(fn ($r) => $r->branch_id . '|' . $r->product_id);
        $stockPairs = BranchStock::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->get()
            ->map(fn ($r) => $r->branch_id . '|' . $r->product_id);
        $merged = $filterDate !== null ? $movementPairs : $movementPairs->merge($stockPairs);
        $pairs = collect();
        foreach ($merged->unique() as $key) {
            [$bId, $pId] = explode('|', $key);
            $pairs->push((object)['branch_id' => $bId, 'product_id' => $pId]);
        }

        $allDiscrepancies = [];
        $rows = [];
        $stepRows = [];

        foreach ($pairs as $pair) {
            $branch = $branches->firstWhere('id', $pair->branch_id);
            $product = $products->get($pair->product_id);
            if (!$branch || !$product) {
                continue;
            }

            $movementsQuery = InventoryMovement::query()
                ->where('branch_id', $pair->branch_id)
                ->where('product_id', $pair->product_id)
                ->orderBy('created_at')
                ->orderBy('id');
            if ($filterDate !== null) {
                $movementsQuery->whereDate('created_at', $filterDate);
            }
            $movements = $movementsQuery->get();

            $runningBalance = $filterDate !== null ? 0 : null;
            $discrepancies = [];
            $steps = [];

            foreach ($movements as $index => $movement) {
                if ($runningBalance === null) {
                    $runningBalance = $movement->quantity_before;
                }
                $expectedBeforeInt = (int) $runningBalance;
                $beforeMatch = $expectedBeforeInt === (int) $movement->quantity_before;
                if (!$beforeMatch) {
                    $discrepancies[] = sprintf(
                        'Movement #%d (%s): expected quantity_before=%d, got %d',
                        $index + 1,
                        $movement->movement_number,
                        $runningBalance,
                        $movement->quantity_before
                    );
                }

                $runningBalance = $runningBalance + (int) $movement->quantity;
                $expectedAfterInt = (int) $runningBalance;
                $afterMatch = $expectedAfterInt === (int) $movement->quantity_after;
                if (!$afterMatch) {
                    $discrepancies[] = sprintf(
                        'Movement #%d (%s): expected quantity_after=%d, got %d',
                        $index + 1,
                        $movement->movement_number,
                        $runningBalance,
                        $movement->quantity_after
                    );
                }

                $steps[] = [
                    'date' => $movement->created_at->toDateString(),
                    'month' => $movement->created_at->month,
                    'day' => $movement->created_at->day,
                    'branch' => $branch->name,
                    'branch_code' => $branch->code ?? '',
                    'product' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'step' => $index + 1,
                    'at' => $movement->created_at->toDateTimeString(),
                    'movement_number' => $movement->movement_number,
                    'type' => $movement->movement_type,
                    'reference' => $this->formatReference($movement),
                    'reference_full' => $this->formatReferenceFull($movement),
                    'reference_type' => $movement->reference_type,
                    'reference_id' => $movement->reference_id,
                    'quantity_before' => $movement->quantity_before,
                    'quantity_delta' => $movement->quantity,
                    'quantity_after' => $movement->quantity_after,
                    'expected_after' => $runningBalance,
                    'reason' => $movement->reason ?? '',
                    'ok' => $beforeMatch && $afterMatch,
                ];
            }

            $currentStock = (int) BranchStock::query()
                ->where('branch_id', $pair->branch_id)
                ->where('product_id', $pair->product_id)
                ->value('quantity');

            if ($movements->isNotEmpty()) {
                if ($runningBalance !== $currentStock) {
                    $discrepancies[] = sprintf(
                        'Final balance from movements (%d) does not match current BranchStock.quantity (%d)',
                        $runningBalance,
                        $currentStock
                    );
                }
            } else {
                if ($currentStock !== 0) {
                    $discrepancies[] = sprintf(
                        'No movement history but BranchStock.quantity = %d',
                        $currentStock
                    );
                }
            }

            $hasDiscrepancy = !empty($discrepancies);
            if ($hasDiscrepancy) {
                $allDiscrepancies[] = [
                    'branch' => $branch->name,
                    'product' => $product->name,
                    'current_stock' => $currentStock,
                    'expected_from_movements' => $runningBalance,
                    'discrepancies' => $discrepancies,
                ];
            }

            $includeInOutput = $hasDiscrepancy || $showOk || $stepsOnly;
            if ($hasDiscrepancy || $showOk) {
                $rows[] = [
                    'date' => now()->toDateString(),
                    'branch' => $branch->name,
                    'product' => $product->name,
                    'current_stock' => $currentStock,
                    'expected_from_movements' => $movements->isNotEmpty() ? $runningBalance : 'N/A',
                    'movements_count' => $movements->count(),
                    'discrepancy' => $hasDiscrepancy ? 'YES' : 'NO',
                    'details' => $hasDiscrepancy ? implode('; ', $discrepancies) : '',
                ];
            }
            if ($includeInOutput) {
                $dayStepIndex = 0;
                foreach ($steps as $s) {
                    if ($filterDate !== null && $s['date'] !== $filterDate->toDateString()) {
                        continue;
                    }
                    $dayStepIndex++;
                    $stepRows[] = array_merge($s, ['step' => $filterDate !== null ? $dayStepIndex : $s['step']]);
                }
            }
        }

        return [
            'step_rows' => $stepRows,
            'rows' => $rows,
            'all_discrepancies' => $allDiscrepancies,
            'filter_date' => $filterDate,
        ];
    }

    /**
     * Apply corrections for a single day: replay movements with start-of-day balance = 0,
     * update quantity_before/quantity_after on movements; then set BranchStock.quantity
     * from the count of non-sold devices per branch/product (prioritising final devices
     * in the system over actual stock level).
     *
     * @param  Carbon  $filterDate  The date to fix (e.g. today or yesterday)
     * @param  string|null  $branchIdOrCode  Optional branch scope
     * @param  string|null  $productIdOrSku  Optional product scope
     * @param  string|null  $userId  User ID for adjustments (defaults to auth)
     * @return array{movements_updated: int, branch_stocks_updated: int, branch_stocks_raised_by_devices: int}
     */
    public function applyFix(
        Carbon $filterDate,
        ?string $branchIdOrCode = null,
        ?string $productIdOrSku = null,
        ?string $userId = null
    ): array {
        $branchQuery = Branch::query();
        if ($branchIdOrCode) {
            $branchQuery->where(function ($q) use ($branchIdOrCode) {
                $q->where('id', $branchIdOrCode)
                    ->orWhereRaw('LOWER(code) = ?', [strtolower($branchIdOrCode)])
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($branchIdOrCode)]);
            });
        }
        $branches = $branchQuery->get();
        if ($branches->isEmpty()) {
            return ['movements_updated' => 0, 'branch_stocks_updated' => 0, 'branch_stocks_raised_by_devices' => 0];
        }

        $productQuery = Product::query();
        if ($productIdOrSku) {
            $productQuery->where('id', $productIdOrSku)
                ->orWhere('sku', $productIdOrSku);
        }
        $products = $productQuery->get();
        if ($products->isEmpty()) {
            return ['movements_updated' => 0, 'branch_stocks_updated' => 0, 'branch_stocks_raised_by_devices' => 0];
        }

        $branchIds = $branches->pluck('id')->all();
        $productIds = $products->pluck('id')->all();

        $todayPairs = InventoryMovement::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->whereDate('created_at', $filterDate)
            ->select('branch_id', 'product_id')
            ->distinct()
            ->get();

        $movementFixes = [];

        foreach ($todayPairs as $row) {
            $pair = (object)['branch_id' => $row->branch_id, 'product_id' => $row->product_id];
            $movements = InventoryMovement::query()
                ->where('branch_id', $pair->branch_id)
                ->where('product_id', $pair->product_id)
                ->whereDate('created_at', $filterDate)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $runningBalance = 0;
            foreach ($movements as $movement) {
                $expectedBefore = $runningBalance;
                $runningBalance += (int) $movement->quantity;
                $expectedAfter = $runningBalance;
                $beforeMatch = $expectedBefore === (int) $movement->quantity_before;
                $afterMatch = $expectedAfter === (int) $movement->quantity_after;
                if (!$beforeMatch || !$afterMatch) {
                    $movementFixes[$movement->id] = [
                        'quantity_before' => $expectedBefore,
                        'quantity_after' => $expectedAfter,
                    ];
                }
            }
        }

        foreach ($movementFixes as $movementId => $values) {
            InventoryMovement::where('id', $movementId)->update([
                'quantity_before' => $values['quantity_before'],
                'quantity_after' => $values['quantity_after'],
            ]);
        }

        // Reconciliation prioritises final devices in the system: set stock from device count per branch/product
        // for every branch×product in scope (including 0 where there are no devices), not from actual stock level.
        $branchStocksAlignedToDevices = 0;
        $adjustmentUserId = $userId ?? (auth()->check() ? (string) auth()->id() : null);
        $pairs = collect($branchIds)->crossJoin($productIds)->map(fn ($p) => (object)['branch_id' => $p[0], 'product_id' => $p[1]]);
        foreach ($pairs as $pair) {
            $branchId = $pair->branch_id;
            $productId = $pair->product_id;
            $deviceCount = Device::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->where('status', '!=', 'sold')
                ->count();
            $currentQtyRaw = BranchStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->value('quantity');
            $currentQty = $currentQtyRaw === null ? 0 : (int) $currentQtyRaw;
            $rowExists = $currentQtyRaw !== null;
            if ($deviceCount !== $currentQty) {
                $adjustmentAmount = $deviceCount - $currentQty;
                $reason = sprintf(
                    'Stock reconciliation: stock set to device count (%d) for this branch/product (was %d).',
                    $deviceCount,
                    $currentQty
                );
                if ($adjustmentUserId !== null) {
                    $adjustment = StockAdjustment::create([
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'stock_take_id' => null,
                        'adjustment_type' => 'reconciliation',
                        'quantity_before' => $currentQty,
                        'quantity_after' => $deviceCount,
                        'adjustment_amount' => $adjustmentAmount,
                        'reason' => $reason,
                        'adjusted_by' => $adjustmentUserId,
                        'approved_by' => $adjustmentUserId,
                        'approved_at' => now(),
                    ]);
                    InventoryMovementService::recordAdjustment(
                        (string) $branchId,
                        (string) $productId,
                        $adjustmentAmount,
                        (string) $adjustment->id,
                        $reason,
                        $adjustmentUserId
                    );
                } else {
                    BranchStock::updateOrCreate(
                        ['branch_id' => $branchId, 'product_id' => $productId],
                        ['quantity' => $deviceCount]
                    );
                }
                $branchStocksAlignedToDevices++;
            } elseif (! $rowExists) {
                // Ensure record exists when missing (e.g. 0 devices, no row yet)
                BranchStock::updateOrCreate(
                    ['branch_id' => $branchId, 'product_id' => $productId],
                    ['quantity' => $deviceCount]
                );
            }
        }

        return [
            'movements_updated' => count($movementFixes),
            'branch_stocks_updated' => $branchStocksAlignedToDevices,
            'branch_stocks_raised_by_devices' => $branchStocksAlignedToDevices,
        ];
    }

    /**
     * Set current stock to the count of available (non-sold) devices per branch/product.
     * Use this to align all branch stock with device counts in one go.
     *
     * @param  string|null  $branchIdOrCode  Optional branch scope (null = all)
     * @param  string|null  $productIdOrSku  Optional product scope (null = all)
     * @param  string|null  $userId  User ID for adjustments (null = no adjustment/movement records)
     * @return array{updated: int, created: int}
     */
    public function syncStockFromDeviceCount(
        ?string $branchIdOrCode = null,
        ?string $productIdOrSku = null,
        ?string $userId = null
    ): array {
        $branchQuery = Branch::query();
        if ($branchIdOrCode) {
            $branchQuery->where(function ($q) use ($branchIdOrCode) {
                $q->where('id', $branchIdOrCode)
                    ->orWhereRaw('LOWER(code) = ?', [strtolower($branchIdOrCode)])
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($branchIdOrCode)]);
            });
        }
        $branches = $branchQuery->get();
        if ($branches->isEmpty()) {
            return ['updated' => 0, 'created' => 0];
        }

        $productQuery = Product::query();
        if ($productIdOrSku) {
            $productQuery->where('id', $productIdOrSku)
                ->orWhere('sku', $productIdOrSku);
        }
        $products = $productQuery->get();
        if ($products->isEmpty()) {
            return ['updated' => 0, 'created' => 0];
        }

        $branchIds = $branches->pluck('id')->all();
        $productIds = $products->pluck('id')->all();
        $adjustmentUserId = $userId ?? (auth()->check() ? (string) auth()->id() : null);
        $pairs = collect($branchIds)->crossJoin($productIds)->map(fn ($p) => (object)['branch_id' => $p[0], 'product_id' => $p[1]]);

        $updated = 0;
        $created = 0;

        foreach ($pairs as $pair) {
            $branchId = $pair->branch_id;
            $productId = $pair->product_id;
            $deviceCount = Device::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->where('status', '!=', 'sold')
                ->count();
            $currentQtyRaw = BranchStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->value('quantity');
            $currentQty = $currentQtyRaw === null ? 0 : (int) $currentQtyRaw;
            $rowExists = $currentQtyRaw !== null;

            if ($deviceCount !== $currentQty) {
                $adjustmentAmount = $deviceCount - $currentQty;
                $reason = sprintf(
                    'Sync stock from devices: current stock set to available device count (%d) for this branch/product (was %d).',
                    $deviceCount,
                    $currentQty
                );
                if ($adjustmentUserId !== null) {
                    $adjustment = StockAdjustment::create([
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'stock_take_id' => null,
                        'adjustment_type' => 'reconciliation',
                        'quantity_before' => $currentQty,
                        'quantity_after' => $deviceCount,
                        'adjustment_amount' => $adjustmentAmount,
                        'reason' => $reason,
                        'adjusted_by' => $adjustmentUserId,
                        'approved_by' => $adjustmentUserId,
                        'approved_at' => now(),
                    ]);
                    InventoryMovementService::recordAdjustment(
                        (string) $branchId,
                        (string) $productId,
                        $adjustmentAmount,
                        (string) $adjustment->id,
                        $reason,
                        $adjustmentUserId
                    );
                } else {
                    BranchStock::updateOrCreate(
                        ['branch_id' => $branchId, 'product_id' => $productId],
                        ['quantity' => $deviceCount]
                    );
                }
                $updated++;
            } elseif (! $rowExists) {
                BranchStock::updateOrCreate(
                    ['branch_id' => $branchId, 'product_id' => $productId],
                    ['quantity' => $deviceCount]
                );
                $created++;
            }
        }

        return ['updated' => $updated, 'created' => $created];
    }

    private function formatReference(InventoryMovement $movement): string
    {
        if (!$movement->reference_type || !$movement->reference_id) {
            return '-';
        }
        $short = class_basename($movement->reference_type);
        return $short . '#' . substr($movement->reference_id, 0, 8) . '…';
    }

    private function formatReferenceFull(InventoryMovement $movement): string
    {
        if (!$movement->reference_type || !$movement->reference_id) {
            return '-';
        }
        $short = class_basename($movement->reference_type);
        return $short . '#' . $movement->reference_id;
    }
}
