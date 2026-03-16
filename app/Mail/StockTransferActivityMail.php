<?php

namespace App\Mail;

use App\Models\StockTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockTransferActivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public StockTransfer $stockTransfer;

    /** @var string e.g. 'created', 'in_transit', 'received', 'partial_received', 'rejected', 'cancelled', 'partial_confirmed', 'returned' */
    public string $activity;

    /** @var array Optional: rejection_reason, quantity_received, received_notes, return_reason */
    public array $payload;

    public function __construct(StockTransfer $stockTransfer, string $activity, array $payload = [])
    {
        $this->stockTransfer = $stockTransfer->loadMissing(['fromBranch', 'toBranch', 'product', 'items.product', 'creator', 'receiver', 'rejectedByUser']);
        $this->activity = $activity;
        $this->payload = $payload;
    }

    public function envelope(): Envelope
    {
        $prefix = 'Stock Transfer';
        $num = $this->stockTransfer->transfer_number ?? ('#' . $this->stockTransfer->id);
        $subjects = [
            'created' => "{$prefix} {$num} – New transfer created",
            'in_transit' => "{$prefix} {$num} – Approved & in transit",
            'received' => "{$prefix} {$num} – Fully received",
            'partial_received' => "{$prefix} {$num} – Partially received (awaiting sender confirmation)",
            'rejected' => "{$prefix} {$num} – Rejected",
            'cancelled' => "{$prefix} {$num} – Cancelled",
            'partial_confirmed' => "{$prefix} {$num} – Partial reception confirmed",
            'returned' => "{$prefix} {$num} – Partial reception returned",
        ];
        $subject = $subjects[$this->activity] ?? "{$prefix} {$num} – Update";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stock-transfer-activity');
    }

    public function attachments(): array
    {
        return [];
    }
}
