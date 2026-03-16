<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class TicketAssignment extends Model
{
    use HasUuid;

    protected $fillable = [
        'ticket_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'activity_summary',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Mark this assignment as unassigned
     */
    public function markUnassigned(?string $activitySummary = null): void
    {
        $this->update([
            'unassigned_at' => now(),
            'is_current' => false,
            'activity_summary' => $activitySummary ?? $this->activity_summary,
        ]);
    }
}
