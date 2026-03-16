<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasUuid;

class TicketEscalation extends Model
{
    use HasUuid;

    protected $fillable = [
        'ticket_id',
        'requested_by',
        'requested_to',
        'reason',
        'status',
        'responded_at',
        'responded_by',
        'response_notes',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requestedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_to');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Accept the escalation
     */
    public function accept(?string $responseNotes = null): void
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'responded_by' => Auth::id(),
            'response_notes' => $responseNotes,
        ]);
    }

    /**
     * Reject the escalation
     */
    public function reject(?string $responseNotes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'responded_by' => Auth::id(),
            'response_notes' => $responseNotes,
        ]);
    }

    /**
     * Cancel the escalation
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'responded_at' => now(),
            'responded_by' => Auth::id(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
