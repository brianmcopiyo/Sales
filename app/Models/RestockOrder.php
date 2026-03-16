<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class RestockOrder extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RECEIVED_PARTIAL = 'received_partial';
    public const STATUS_RECEIVED_FULL = 'received_full';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'order_batch',
        'reference_number',
        'branch_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'total_acquisition_cost',
        'status',
        'dealership_id',
        'dealership_name',
        'expected_at',
        'ordered_at',
        'received_at',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
            'total_acquisition_cost' => 'decimal:2',
            'expected_at' => 'date',
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function dealership()
    {
        return $this->belongsTo(Dealership::class);
    }

    /** Display label: linked dealership name or free-text dealership_name. */
    public function getDealershipDisplayNameAttribute(): ?string
    {
        return $this->dealership?->name ?? $this->dealership_name;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** Orders in the same batch (when this order is part of a bulk order). */
    public function batchOrders()
    {
        if (!$this->order_batch) {
            return collect();
        }
        return static::with(['product', 'branch'])
            ->where('order_batch', $this->order_batch)
            ->orderBy('order_number')
            ->get();
    }

    /** Display order number: batch identifier for bulk, otherwise single order number. */
    public function getDisplayOrderNumberAttribute(): string
    {
        return $this->order_batch ?? $this->order_number;
    }

    public function isBatchOrder(): bool
    {
        return !empty($this->order_batch);
    }

    public function getQuantityOutstandingAttribute(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RECEIVED_PARTIAL], true);
    }

    public function isFullyReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED_FULL;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RECEIVED_PARTIAL], true);
    }

    /** Generate next order number (base for single or batch). Batch lines use base + '-' + line index. */
    public static function generateOrderNumber(): string
    {
        $prefix = 'RO-' . now()->format('Ymd');
        $numbers = static::where('order_number', 'like', $prefix . '-%')->pluck('order_number');
        $maxSeq = 0;
        foreach ($numbers as $num) {
            $parts = explode('-', $num);
            if (count($parts) >= 3 && is_numeric($parts[2])) {
                $seq = (int) $parts[2];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }
        return $prefix . '-' . str_pad((string) ($maxSeq + 1), 4, '0', STR_PAD_LEFT);
    }
}
