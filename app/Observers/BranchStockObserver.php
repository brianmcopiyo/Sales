<?php

namespace App\Observers;

use App\Mail\StockActivityMail;
use App\Models\BranchStock;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class BranchStockObserver
{
    /** Previous quantity before update (for detecting crossing below limit). */
    private static array $previousQuantities = [];

    public function updating(BranchStock $branchStock): void
    {
        self::$previousQuantities[$branchStock->id] = $branchStock->quantity;
    }

    public function updated(BranchStock $branchStock): void
    {
        $previousQuantity = self::$previousQuantities[$branchStock->id] ?? null;
        unset(self::$previousQuantities[$branchStock->id]);

        if ($previousQuantity === null) {
            return;
        }

        $branchStock->loadMissing('product');
        $limit = (int) ($branchStock->product->minimum_stock_level ?? 10);
        $newQuantity = (int) $branchStock->quantity;

        // Clear notification flags when restocked above threshold so we can alert again next time
        if ($newQuantity > $limit) {
            $this->clearLowStockFlagIfSet($branchStock);
        }
        if ($newQuantity > 0) {
            $this->clearZeroStockFlagIfSet($branchStock);
        }

        // Low stock: notify only once when crossing below limit, until restocked above limit
        if ($previousQuantity > $limit && $newQuantity <= $limit && $branchStock->low_stock_notified_at === null) {
            $this->sendLowStockAlert($branchStock, $newQuantity, $limit);
            $this->markLowStockNotified($branchStock);
        }

        // Zero stock: send a separate notification once when stock reaches 0, until restocked
        if ($previousQuantity > 0 && $newQuantity === 0 && $branchStock->zero_stock_notified_at === null) {
            $this->sendZeroStockAlert($branchStock);
            $this->markZeroStockNotified($branchStock);
        }
    }

    private function clearLowStockFlagIfSet(BranchStock $branchStock): void
    {
        if ($branchStock->low_stock_notified_at !== null) {
            BranchStock::withoutEvents(fn() => $branchStock->update(['low_stock_notified_at' => null]));
        }
    }

    private function clearZeroStockFlagIfSet(BranchStock $branchStock): void
    {
        if ($branchStock->zero_stock_notified_at !== null) {
            BranchStock::withoutEvents(fn() => $branchStock->update(['zero_stock_notified_at' => null]));
        }
    }

    private function markLowStockNotified(BranchStock $branchStock): void
    {
        BranchStock::withoutEvents(fn() => $branchStock->update(['low_stock_notified_at' => now()]));
    }

    private function markZeroStockNotified(BranchStock $branchStock): void
    {
        BranchStock::withoutEvents(fn() => $branchStock->update(['zero_stock_notified_at' => now()]));
    }

    private function sendLowStockAlert(BranchStock $branchStock, int $currentQuantity, int $limit): void
    {
        $branchStock->loadMissing(['branch', 'product']);
        $branchName = $branchStock->branch->name;
        $productName = $branchStock->product->name;

        $title = 'Low stock alert';
        $message = "{$productName} at {$branchName} is below the minimum level: {$currentQuantity} units (minimum: {$limit}). Consider restocking.";
        $url = route('stock-management.index');

        $this->sendStockAlertToBranch($branchStock, $title, $message, $url, 'low_stock', [
            'branch_stock_id' => $branchStock->id,
            'branch_id' => $branchStock->branch_id,
            'product_id' => $branchStock->product_id,
            'quantity' => $currentQuantity,
            'minimum_stock_level' => $limit,
        ]);
    }

    private function sendZeroStockAlert(BranchStock $branchStock): void
    {
        $branchStock->loadMissing(['branch', 'product']);
        $branchName = $branchStock->branch->name;
        $productName = $branchStock->product->name;

        $title = 'Out of stock';
        $message = "{$productName} at {$branchName} has reached 0 units. Restock as soon as possible.";
        $url = route('stock-management.index');

        $this->sendStockAlertToBranch($branchStock, $title, $message, $url, 'zero_stock', [
            'branch_stock_id' => $branchStock->id,
            'branch_id' => $branchStock->branch_id,
            'product_id' => $branchStock->product_id,
            'quantity' => 0,
        ]);
    }

    private function sendStockAlertToBranch(BranchStock $branchStock, string $title, string $message, string $url, string $type, array $payload): void
    {
        $users = User::usersWithStockManagementPermission([$branchStock->branch_id]);
        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new AppNotification($title, $message, $url, $type, $payload));

        $emails = $users->pluck('email')->filter()->unique()->values()->all();
        if (! empty($emails)) {
            Mail::to($emails)->queue(new StockActivityMail($title, $message, $url, 'View stock management'));
        }
    }
}
