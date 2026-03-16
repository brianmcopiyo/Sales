<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use App\Traits\HasUuid;
use App\Services\InventoryMovementService;

class Device extends Model
{
    use HasFactory, HasUuid;

    protected static function booted(): void
    {
        static::creating(function (Device $model) {
            if ($model->branch_id === null && auth()->check()) {
                $user = auth()->user();
                if ($user->branch_id !== null) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });

        static::created(function (Device $device) {
            // Skip stock adjustment if:
            // 1. Device is created during restock (stock is already adjusted in restock operation)
            // 2. Device has already been counted in stock (prevents re-entry)
            if ($device->restock_order_id !== null || $device->stock_counted) {
                return;
            }

            // Adjust stock levels: increase by 1 when a device is created
            // (devices represent available inventory stock)
            if ($device->branch_id && $device->product_id) {
                // Record inventory movement (service updates or creates BranchStock)
                InventoryMovementService::record(
                    $device->branch_id,
                    $device->product_id,
                    'receipt',
                    1,
                    'App\Models\Device',
                    $device->id,
                    'Device created',
                    "Device IMEI: {$device->imei}",
                    auth()->id()
                );

                // Mark device as counted in stock to prevent re-entry (only if column exists)
                if (Schema::hasColumn('devices', 'stock_counted')) {
                    $device->update(['stock_counted' => true]);
                }
            }
        });

        // A device cannot be attached to a customer and still available.
        static::saving(function (Device $model) {
            if ($model->status === 'sold') {
                return;
            }
            if ($model->status === 'available') {
                $model->customer_id = null;
                return;
            }
            if ($model->customer_id !== null) {
                $model->status = 'assigned';
                return;
            }
            // assigned with no customer => treat as available
            if ($model->status === 'assigned') {
                $model->status = 'available';
            }
        });
    }

    protected $fillable = [
        'imei',
        'product_id',
        'branch_id',
        'restock_order_id',
        'customer_id',
        'sale_id',
        'status',
        'sold_by_user_id',
        'notes',
        'has_received_disbursement',
        'stock_counted',
    ];

    protected function casts(): array
    {
        return [
            'has_received_disbursement' => 'boolean',
            'stock_counted' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function restockOrder()
    {
        return $this->belongsTo(RestockOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function soldBy()
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(DeviceStatusLog::class)->latest();
    }

    public function saleItem()
    {
        return $this->hasOne(SaleItem::class);
    }

    public function replacementsAsOriginal()
    {
        return $this->hasMany(DeviceReplacement::class, 'original_device_id');
    }

    public function replacementsAsReplacement()
    {
        return $this->hasMany(DeviceReplacement::class, 'replacement_device_id');
    }

    /**
     * Scope: devices that can be sold — status is 'available' or attached to a cancelled sale (backwards compatibility).
     */
    public function scopeAvailableForSale($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'available')
                ->orWhereHas('sale', fn ($s) => $s->where('status', 'cancelled'));
        });
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /** True if this device can be sold (available or attached to a cancelled sale). */
    public function isAvailableForSale(): bool
    {
        if ($this->status === 'available') {
            return true;
        }
        return $this->sale_id && $this->relationLoaded('sale') && $this->sale?->status === 'cancelled';
    }

    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    public function hasReceivedDisbursement(): bool
    {
        return $this->has_received_disbursement === true;
    }

    public function markDisbursementReceived(): void
    {
        $this->update(['has_received_disbursement' => true]);
    }
}
