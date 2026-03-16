<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasUuid;

class Ticket extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'ticket_number',
        'sequence_number',
        'customer_id',
        'assigned_to',
        'subject',
        'description',
        'priority',
        'status',
        'category',
        'sale_id',
        'product_id',
        'branch_id',
        'disbursement_id',
        'resolved_at',
        'first_response_at',
        'last_response_at',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_response_at' => 'datetime',
            'due_date' => 'datetime',
            'sequence_number' => 'integer',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(TicketTag::class, 'ticket_tag_ticket');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function disbursement()
    {
        return $this->belongsTo(CustomerDisbursement::class, 'disbursement_id');
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(TicketAssignment::class)->where('is_current', true);
    }

    public function escalations()
    {
        return $this->hasMany(TicketEscalation::class);
    }

    public function pendingEscalations()
    {
        return $this->hasMany(TicketEscalation::class)->where('status', 'pending');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->sequence_number)) {
                // Get the next sequence number
                $lastTicket = static::orderBy('sequence_number', 'desc')->first();
                $ticket->sequence_number = $lastTicket && $lastTicket->sequence_number ? $lastTicket->sequence_number + 1 : 1;
            }
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'TKT-' . str_pad($ticket->sequence_number ?? 1, 6, '0', STR_PAD_LEFT);
            }
        });

        // Track assignment history when assigned_to changes
        static::updating(function ($ticket) {
            // Only track if assigned_to is being changed
            if ($ticket->isDirty('assigned_to')) {
                $oldAssignedTo = $ticket->getOriginal('assigned_to');
                $newAssignedTo = $ticket->assigned_to;

                // Mark previous assignment as unassigned
                if ($oldAssignedTo) {
                    $previousAssignment = TicketAssignment::where('ticket_id', $ticket->id)
                        ->where('assigned_to', $oldAssignedTo)
                        ->where('is_current', true)
                        ->first();

                    if ($previousAssignment) {
                        $previousAssignment->markUnassigned();
                    }
                }

                // Create new assignment record
                if ($newAssignedTo) {
                    TicketAssignment::create([
                        'ticket_id' => $ticket->id,
                        'assigned_to' => $newAssignedTo,
                        'assigned_by' => Auth::id() ?? $oldAssignedTo ?? $newAssignedTo,
                        'assigned_at' => now(),
                        'is_current' => true,
                    ]);
                }
            }
        });

        // Track initial assignment when ticket is created
        static::created(function ($ticket) {
            if ($ticket->assigned_to) {
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'assigned_to' => $ticket->assigned_to,
                    'assigned_by' => Auth::id() ?? $ticket->assigned_to,
                    'assigned_at' => now(),
                    'is_current' => true,
                ]);
            }
        });
    }

    public function updateLastResponse()
    {
        $this->update(['last_response_at' => now()]);
    }

    public function markFirstResponse()
    {
        if (!$this->first_response_at) {
            $this->update(['first_response_at' => now()]);
        }
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['resolved', 'closed']);
    }
}
