<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StockReconciliationCommand extends Command
{
    protected $signature = 'stock:reconcile
                            {--branch= : Branch ID or code (optional, otherwise all branches)}
                            {--product= : Product ID or SKU (optional, otherwise all products)}
                            {--today : Only include movements and branch/product activity for today}
                            {--yesterday : Only include movements and branch/product activity for yesterday}
                            {--show-ok : Show branch/product rows that have no discrepancies}
                            {--steps-only : Only display the step-by-step history table (no summary or discrepancy blocks)}
                            {--fix : Apply corrections only for today (start-of-day balance=0); fix today movements and BranchStock}
                            {--export= : Export to CSV path (optional)}';

    protected $description = 'Step-by-step stock adjustment history by branch by product with discrepancy detection.';

    public function handle(): int
    {
        $branchIdOrCode = $this->option('branch');
        $productIdOrSku = $this->option('product');
        $today = $this->option('today');
        $yesterday = $this->option('yesterday');
        $showOk = $this->option('show-ok');
        $stepsOnly = $this->option('steps-only');
        $fix = $this->option('fix');
        $exportPath = $this->option('export');

        if ($today && $yesterday) {
            $this->error('Use only one of --today or --yesterday.');
            return self::FAILURE;
        }

        $filterDate = null;
        if ($today) {
            $filterDate = Carbon::today();
        } elseif ($yesterday) {
            $filterDate = Carbon::yesterday();
        }

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
            $this->error('No branches found for the given filters.');
            $existing = Branch::query()->get(['code', 'name']);
            if ($existing->isNotEmpty()) {
                $this->line('Available branches (use --branch=<code> or --branch=<name>):');
                foreach ($existing as $b) {
                    $this->line('  - ' . ($b->code ?? '—') . '  ' . $b->name);
                }
            }
            return self::FAILURE;
        }

        $productQuery = Product::query();
        if ($productIdOrSku) {
            $productQuery->where('id', $productIdOrSku)
                ->orWhere('sku', $productIdOrSku);
        }
        $products = $productQuery->get()->keyBy('id');
        if ($products->isEmpty()) {
            $this->error('No products found for the given filters.');
            return self::FAILURE;
        }

        $branchIds = $branches->pluck('id')->all();
        $productIds = $products->pluck('id')->all();

        // Pairs (branch_id, product_id) that have movements or current stock
        $pairs = collect();
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
        foreach ($merged->unique() as $key) {
            [$bId, $pId] = explode('|', $key);
            $pairs->push((object)['branch_id' => $bId, 'product_id' => $pId]);
        }

        $allDiscrepancies = [];
        $rows = [];
        $stepRows = [];
        /** @var array<string, array{quantity_before: int, quantity_after: int}> movement id => correct values */
        $movementFixes = [];
        /** @var array<string, int> "branch_id|product_id" => correct quantity */
        $branchStockFixes = [];

        foreach ($pairs as $pair) {
            $branch = $branches->firstWhere('id', $pair->branch_id);
            $product = $products->get($pair->product_id);
            if (!$branch || !$product) {
                continue;
            }

            // 1. Reconcile: when filtering by date (--today/--yesterday), replay only that day's movements starting at 0 (matches --fix logic)
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
                    'movement_id' => $movement->id,
                    'type' => $movement->movement_type,
                    'reference' => $this->formatReference($movement),
                    'reference_full' => $this->formatReferenceFull($movement),
                    'quantity_before' => $movement->quantity_before,
                    'quantity_delta' => $movement->quantity,
                    'quantity_after' => $movement->quantity_after,
                    'expected_after' => $runningBalance,
                    'reason' => $movement->reason ?? '',
                    'ok' => $beforeMatch && $afterMatch,
                ];
            }

            // 2. Check current stock balance (after reconciling from movements)
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
                        'No movement history but BranchStock.quantity = %d (opening balance or data entry without movement)',
                        $currentStock
                    );
                }
            }

            $hasDiscrepancy = !empty($discrepancies);
            if ($hasDiscrepancy) {
                $allDiscrepancies[] = [
                    'branch' => $branch->name,
                    'branch_id' => $branch->id,
                    'product' => $product->name,
                    'product_id' => $product->id,
                    'current_stock' => $currentStock,
                    'expected_from_movements' => $runningBalance,
                    'discrepancies' => $discrepancies,
                ];
            }

            $includeInOutput = $hasDiscrepancy || $showOk || $stepsOnly;
            if ($hasDiscrepancy || $showOk) {
                $reportDate = now();
                $rows[] = [
                    'date' => $reportDate->toDateString(),
                    'month' => $reportDate->month,
                    'day' => $reportDate->day,
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

        if (!$stepsOnly) {
            $this->newLine();
            if ($filterDate !== null) {
                $this->info('=== Stock reconciliation report (activity on ' . $filterDate->toDateString() . ') ===');
            } else {
                $this->info('=== Stock reconciliation report ===');
            }
            $this->table(
                ['Date', 'Month', 'Day', 'Branch', 'Product', 'Current stock', 'Expected (from movements)', 'Movements', 'Discrepancy', 'Details'],
                array_map(fn ($r) => [$r['date'], $r['month'], $r['day'], $r['branch'], $r['product'], $r['current_stock'], $r['expected_from_movements'], $r['movements_count'], $r['discrepancy'], $r['details']], $rows)
            );

            if (!empty($stepRows)) {
                $this->newLine();
                $this->info('=== Step-by-step history (by branch / product) ===');
                $this->outputStepTable($stepRows, false);
            }

            if (!empty($allDiscrepancies)) {
                $this->newLine();
                $this->warn('Discrepancy summary: ' . count($allDiscrepancies) . ' branch/product combination(s) with issues.');
                foreach ($allDiscrepancies as $d) {
                    $this->line('  • ' . $d['branch'] . ' / ' . $d['product'] . ':');
                    foreach ($d['discrepancies'] as $msg) {
                        $this->line('    - ' . $msg);
                    }
                }
            } else {
                $this->newLine();
                $this->info('No discrepancies detected.');
            }
        } elseif (!empty($stepRows)) {
            $this->outputStepTable($stepRows, true);
        }

        if ($exportPath) {
            $this->exportCsv($exportPath, $rows, $stepRows, $allDiscrepancies);
            $this->info('Exported to: ' . $exportPath);
        }

        // Fix only today: start-of-day balance = 0, replay today's movements only (stock takes, adjustments, transfers)
        if ($fix) {
            $todayDate = Carbon::today();
            $todayPairs = InventoryMovement::query()
                ->whereIn('branch_id', $branchIds)
                ->whereIn('product_id', $productIds)
                ->whereDate('created_at', $todayDate)
                ->select('branch_id', 'product_id')
                ->distinct()
                ->get();
            foreach ($todayPairs as $row) {
                $pair = (object)['branch_id' => $row->branch_id, 'product_id' => $row->product_id];
                $todayMovements = InventoryMovement::query()
                    ->where('branch_id', $pair->branch_id)
                    ->where('product_id', $pair->product_id)
                    ->whereDate('created_at', $todayDate)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();
                $runningBalance = 0;
                foreach ($todayMovements as $movement) {
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
                $branchStockFixes[$pair->branch_id . '|' . $pair->product_id] = $runningBalance;
            }
        }

        if ($fix && (!empty($movementFixes) || !empty($branchStockFixes))) {
            $this->newLine();
            $this->warn('Fix applies only to today\'s movements (start-of-day balance = 0).');
            if (!$this->option('no-interaction') && !$this->confirm(sprintf(
                'Apply %d movement correction(s) and %d branch stock correction(s)?',
                count($movementFixes),
                count($branchStockFixes)
            ), true)) {
                $this->warn('Fix skipped by user.');
            } else {
                foreach ($movementFixes as $movementId => $values) {
                    InventoryMovement::where('id', $movementId)->update([
                        'quantity_before' => $values['quantity_before'],
                        'quantity_after' => $values['quantity_after'],
                    ]);
                }
                foreach ($branchStockFixes as $key => $quantity) {
                    [$branchId, $productId] = explode('|', $key);
                    BranchStock::query()
                        ->where('branch_id', $branchId)
                        ->where('product_id', $productId)
                        ->update(['quantity' => $quantity]);
                }
                $this->info('Applied ' . count($movementFixes) . ' movement(s) and ' . count($branchStockFixes) . ' branch stock balance(s).');
            }
        }

        return empty($allDiscrepancies) ? self::SUCCESS : self::FAILURE;
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

    private function outputStepTable(array $stepRows, bool $detailed): void
    {
        if ($detailed) {
            $headers = ['Date', 'At', 'Branch code', 'Branch', 'Product SKU', 'Product', 'Step', 'Movement', 'Type', 'Reference', 'Qty before', 'Delta', 'Qty after', 'Expected after', 'OK', 'Reason'];
            $this->table($headers, array_map(function ($s) {
                return [
                    $s['date'],
                    $s['at'],
                    $s['branch_code'] ?? '',
                    $s['branch'],
                    $s['product_sku'] ?? '',
                    $s['product'],
                    $s['step'],
                    $s['movement_number'],
                    $s['type'],
                    $s['reference_full'] ?? $s['reference'],
                    $s['quantity_before'],
                    $s['quantity_delta'],
                    $s['quantity_after'],
                    $s['expected_after'],
                    $s['ok'] ? 'Yes' : 'No',
                    $s['reason'],
                ];
            }, $stepRows));
        } else {
            $this->table(
                ['Date', 'Month', 'Day', 'Branch', 'Product', 'Step', 'At', 'Movement', 'Type', 'Reference', 'Qty before', 'Delta', 'Qty after', 'Expected after', 'OK', 'Reason'],
                array_map(function ($s) {
                    return [
                        $s['date'],
                        $s['month'],
                        $s['day'],
                        $s['branch'],
                        $s['product'],
                        $s['step'],
                        $s['at'],
                        $s['movement_number'],
                        $s['type'],
                        $s['reference'],
                        $s['quantity_before'],
                        $s['quantity_delta'],
                        $s['quantity_after'],
                        $s['expected_after'],
                        $s['ok'] ? 'Yes' : 'No',
                        substr($s['reason'], 0, 40),
                    ];
                }, $stepRows)
            );
        }
    }

    private function exportCsv(string $path, array $rows, array $stepRows, array $allDiscrepancies): void
    {
        $handle = fopen($path, 'w');
        if (!$handle) {
            $this->warn('Could not open file for export: ' . $path);
            return;
        }

        fputcsv($handle, ['Summary: Date', 'Month', 'Day', 'Branch', 'Product', 'Current stock', 'Expected from movements', 'Movements count', 'Discrepancy', 'Details']);
        foreach ($rows as $r) {
            fputcsv($handle, [$r['date'], $r['month'], $r['day'], $r['branch'], $r['product'], $r['current_stock'], $r['expected_from_movements'], $r['movements_count'], $r['discrepancy'], $r['details']]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Step history: Date', 'Month', 'Day', 'Branch', 'Product', 'Step', 'At', 'Movement', 'Type', 'Reference', 'Qty before', 'Delta', 'Qty after', 'Expected after', 'OK', 'Reason']);
        foreach ($stepRows as $s) {
            fputcsv($handle, [
                $s['date'], $s['month'], $s['day'], $s['branch'], $s['product'], $s['step'], $s['at'], $s['movement_number'], $s['type'], $s['reference'],
                $s['quantity_before'], $s['quantity_delta'], $s['quantity_after'], $s['expected_after'],
                $s['ok'] ? 'Yes' : 'No', $s['reason'],
            ]);
        }

        $reportDate = now();
        fputcsv($handle, []);
        fputcsv($handle, ['Discrepancies: Date', 'Month', 'Day', 'Branch', 'Product', 'Current stock', 'Expected', 'Message']);
        foreach ($allDiscrepancies as $d) {
            foreach ($d['discrepancies'] as $msg) {
                fputcsv($handle, [$reportDate->toDateString(), $reportDate->month, $reportDate->day, $d['branch'], $d['product'], $d['current_stock'], $d['expected_from_movements'] ?? '', $msg]);
            }
        }

        fclose($handle);
    }
}
