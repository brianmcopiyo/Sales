<?php

namespace App\Notifications;

use App\Models\StockTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockTransferActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public StockTransfer $stockTransfer,
        public string $activity,
        public array $payload = []
    ) {
        $this->stockTransfer->loadMissing(['fromBranch', 'toBranch', 'product', 'items.product']);
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $num = $this->stockTransfer->transfer_number ?? ('#' . $this->stockTransfer->id);
        $titles = [
            'created' => "New transfer {$num}",
            'in_transit' => "Transfer {$num} in transit",
            'received' => "Transfer {$num} received",
            'partial_received' => "Transfer {$num} partially received",
            'rejected' => "Transfer {$num} rejected",
            'cancelled' => "Transfer {$num} cancelled",
            'partial_confirmed' => "Transfer {$num} partial confirmed",
            'returned' => "Transfer {$num} returned",
        ];
        $title = $titles[$this->activity] ?? "Transfer {$num} update";

        $productName = $this->stockTransfer->product?->name ?? $this->stockTransfer->items->first()?->product?->name ?? 'Product';
        $from = $this->stockTransfer->fromBranch->name ?? 'Branch';
        $to = $this->stockTransfer->toBranch->name ?? 'Branch';
        $message = "{$productName}: {$from} → {$to}";

        return [
            'title' => $title,
            'message' => $message,
            'action_url' => route('stock-transfers.show', $this->stockTransfer),
            'activity' => $this->activity,
            'transfer_id' => $this->stockTransfer->id,
            'transfer_number' => $this->stockTransfer->transfer_number,
            'payload' => $this->payload,
        ];
    }
}
