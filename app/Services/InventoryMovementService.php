<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\BranchStock;
use Illuminate\Support\Str;

class InventoryMovementService
{
    /**
     * Record an inventory movement
     */
    public static function record(
        string $branchId,
        string $productId,
        string $movementType,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
        ?string $notes = null,
        ?string $userId = null
    ): InventoryMovement {
        // Get current stock level (read before any update so movement reflects true before/after)
        $branchStock = BranchStock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        $quantityBefore = max(0, $branchStock ? (int) $branchStock->quantity : 0);
        $quantityAfter = max(0, $quantityBefore + $quantity);

        $movement = InventoryMovement::create([
            'movement_number' => 'MOV-' . strtoupper(uniqid()),
            'branch_id' => $branchId,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'notes' => $notes,
            'created_by' => $userId ?? auth()->id(),
        ]);

        // Apply the same change to BranchStock so movements and stock level stay in sync (single source of truth)
        BranchStock::updateOrCreate(
            ['branch_id' => $branchId, 'product_id' => $productId],
            ['quantity' => $quantityAfter]
        );

        return $movement;
    }

    /**
     * Record stock transfer movement (outgoing)
     */
    public static function recordTransferOut(
        string $branchId,
        string $productId,
        int $quantity,
        string $transferId,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'transfer',
            -abs($quantity), // Negative for outgoing
            'App\Models\StockTransfer',
            $transferId,
            'Stock transfer - outgoing',
            null,
            $userId
        );
    }

    /**
     * Record stock transfer movement (incoming)
     */
    public static function recordTransferIn(
        string $branchId,
        string $productId,
        int $quantity,
        string $transferId,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'transfer',
            abs($quantity), // Positive for incoming
            'App\Models\StockTransfer',
            $transferId,
            'Stock transfer - incoming',
            null,
            $userId
        );
    }

    /**
     * Record stock adjustment movement
     */
    public static function recordAdjustment(
        string $branchId,
        string $productId,
        int $adjustmentAmount,
        string $adjustmentId,
        ?string $reason = null,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'adjustment',
            $adjustmentAmount,
            'App\Models\StockAdjustment',
            $adjustmentId,
            $reason ?? 'Stock adjustment',
            null,
            $userId
        );
    }

    /**
     * Record sale movement
     */
    public static function recordSale(
        string $branchId,
        string $productId,
        int $quantity,
        string $saleId,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'sale',
            -abs($quantity), // Negative for sales
            'App\Models\Sale',
            $saleId,
            'Sale completed',
            null,
            $userId
        );
    }

    /**
     * Record stock take adjustment
     */
    public static function recordStockTake(
        string $branchId,
        string $productId,
        int $adjustmentAmount,
        string $stockTakeId,
        ?string $reason = null,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'stock_take',
            $adjustmentAmount,
            'App\Models\StockTake',
            $stockTakeId,
            $reason ?? 'Stock take adjustment',
            null,
            $userId
        );
    }

    /**
     * Record sale cancellation: device(s) returned to stock (positive quantity).
     */
    public static function recordSaleCancellation(
        string $branchId,
        string $productId,
        int $quantity,
        string $saleId,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'return',
            abs($quantity),
            'App\Models\Sale',
            $saleId,
            'Sale cancelled - device(s) returned to stock',
            null,
            $userId
        );
    }

    /**
     * Record restock order receipt (goods received against a restock order)
     */
    public static function recordRestockReceipt(
        string $branchId,
        string $productId,
        int $quantity,
        string $restockOrderId,
        ?string $notes = null,
        ?string $userId = null
    ): InventoryMovement {
        return self::record(
            $branchId,
            $productId,
            'receipt',
            abs($quantity),
            'App\Models\RestockOrder',
            $restockOrderId,
            'Restock order received',
            $notes,
            $userId
        );
    }
}
