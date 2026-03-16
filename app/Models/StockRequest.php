<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StockRequest extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_FULFILLED = 'partially_fulfilled';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'requesting_branch_id',
        'requested_from_branch_id',
        'product_id',
        'quantity_requested',
        'quantity_fulfilled',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'stock_transfer_id',
        'closed_at',
        'closed_by',
        'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'integer',
            'quantity_fulfilled' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function requestingBranch()
    {
        return $this->belongsTo(Branch::class, 'requesting_branch_id');
    }

    public function requestedFromBranch()
    {
        return $this->belongsTo(Branch::class, 'requested_from_branch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * All transfers created from this request. Each partial or full fulfillment creates a separate transfer;
     * multiple fulfillments = multiple transfer records, all linked to this same request.
     */
    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    /** Remaining units to fulfill (quantity_requested - quantity_fulfilled). */
    public function remainderQuantity(): int
    {
        return max(0, $this->quantity_requested - (int) $this->quantity_fulfilled);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPartiallyFulfilled(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_FULFILLED;
    }

    /** Request was closed by the sender (no more fulfillments). */
    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /** Can the requested-from branch still fulfill more (pending or partially_fulfilled, remainder > 0, not closed). */
    public function canFulfillMore(): bool
    {
        return !$this->isClosed()
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIALLY_FULFILLED], true)
            && $this->remainderQuantity() > 0;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
