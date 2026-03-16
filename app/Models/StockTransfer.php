<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StockTransfer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'transfer_number',
        'from_branch_id',
        'to_branch_id',
        'product_id',
        'restock_order_id',
        'stock_request_id',
        'quantity',
        'quantity_received',
        'status',
        'created_by',
        'received_by',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'notes',
        'received_notes',
        'transferred_at',
        'received_at',
        'sender_confirmed_by',
        'sender_confirmed_at',
        'return_reason',
        'returned_by',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
            'received_at' => 'datetime',
            'rejected_at' => 'datetime',
            'sender_confirmed_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function restockOrder()
    {
        return $this->belongsTo(RestockOrder::class);
    }

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    /**
     * Line items (one or more products per transfer). Always use for multi-item; legacy transfers have one item (backfilled).
     */
    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }

    /**
     * Total quantity across all items. Matches legacy quantity when single item.
     */
    public function getTotalQuantityAttribute(): int
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return (int) $this->items->sum('quantity');
        }
        return (int) $this->quantity;
    }

    /**
     * Total quantity received across all items.
     */
    public function getTotalQuantityReceivedAttribute(): ?int
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            $sum = $this->items->sum(fn ($i) => $i->quantity_received ?? 0);
            return $sum > 0 ? (int) $sum : null;
        }
        return $this->quantity_received;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function senderConfirmedBy()
    {
        return $this->belongsTo(User::class, 'sender_confirmed_by');
    }

    public function returnedByUser()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function receptionAttachments()
    {
        return $this->hasMany(StockTransferReceptionAttachment::class);
    }

    /**
     * Quantity actually received (for partial reception). When null, treat as full quantity for backward compatibility.
     */
    public function getEffectiveQuantityReceivedAttribute(): int
    {
        $totalReceived = $this->total_quantity_received;
        if ($totalReceived !== null) {
            return $totalReceived;
        }
        return $this->quantity_received ?? $this->quantity;
    }

    /**
     * Whether this transfer was partially received (received less than sent).
     */
    public function isPartialReception(): bool
    {
        if ($this->status !== 'received') {
            return false;
        }
        $totalQty = $this->total_quantity;
        $totalReceived = $this->total_quantity_received;
        if ($totalReceived === null) {
            return false;
        }
        return $totalReceived < $totalQty;
    }

    /**
     * Whether this transfer is awaiting sender confirmation of partial reception.
     */
    public function isPendingSenderConfirmation(): bool
    {
        return $this->status === 'pending_sender_confirmation';
    }

    /**
     * Get one user per branch: the branch contact (user with stock-transfers.receive-notifications).
     * Used for in-app and email notifications.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getNotificationUsers(): \Illuminate\Support\Collection
    {
        $users = collect();
        $permissionSlug = 'stock-transfers.receive-notifications';
        foreach ([$this->from_branch_id, $this->to_branch_id] as $branchId) {
            if (!$branchId) {
                continue;
            }
            $user = User::where('branch_id', $branchId)
                ->whereHas('roleModel', function ($q) use ($permissionSlug) {
                    $q->whereHas('permissions', function ($p) use ($permissionSlug) {
                        $p->where('slug', $permissionSlug);
                    });
                })
                ->first();
            if ($user) {
                $users->push($user);
            }
        }
        return $users->unique('id')->values();
    }

    /**
     * Get one email per branch: the branch contact (user with stock-transfers.receive-notifications).
     * Used for stock transfer activity emails so only the branch admin receives, not the entire branch.
     *
     * @return array<string>
     */
    public function getNotificationEmails(): array
    {
        return $this->getNotificationUsers()
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = 'TRF-' . strtoupper(uniqid());
            }
        });
    }
}
