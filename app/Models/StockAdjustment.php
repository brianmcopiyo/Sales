<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StockAdjustment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'adjustment_number',
        'branch_id',
        'product_id',
        'stock_take_id',
        'adjustment_type',
        'quantity_before',
        'quantity_after',
        'adjustment_amount',
        'reason',
        'adjusted_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'adjustment_amount' => 'integer',
            'approved_at' => 'datetime',
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

    public function stockTake()
    {
        return $this->belongsTo(StockTake::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Auto-generate adjustment number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (empty($adjustment->adjustment_number)) {
                $adjustment->adjustment_number = 'ADJ-' . strtoupper(uniqid());
            }
        });
    }

    // Helper methods
    public function isIncrease(): bool
    {
        return $this->adjustment_amount > 0;
    }

    public function isDecrease(): bool
    {
        return $this->adjustment_amount < 0;
    }

    public function isFromStockTake(): bool
    {
        return $this->adjustment_type === 'stock_take' && $this->stock_take_id !== null;
    }
}
