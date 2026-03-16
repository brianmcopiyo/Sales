<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StockTakeItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'stock_take_id',
        'product_id',
        'system_quantity',
        'physical_quantity',
        'variance',
        'first_count',
        'recount',
        'notes',
        'submitted_imeis',
        'counted_by',
        'counted_at',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'integer',
            'physical_quantity' => 'integer',
            'variance' => 'integer',
            'first_count' => 'integer',
            'recount' => 'integer',
            'submitted_imeis' => 'array',
            'counted_at' => 'datetime',
        ];
    }

    public function stockTake()
    {
        return $this->belongsTo(StockTake::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    // Calculate variance automatically
    public function calculateVariance(): int
    {
        if ($this->physical_quantity === null) {
            return 0;
        }
        return $this->physical_quantity - $this->system_quantity;
    }

    // Update variance when physical quantity changes
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->physical_quantity !== null) {
                $item->variance = $item->calculateVariance();
            }
        });
    }

    // Variance type helpers
    public function hasVariance(): bool
    {
        return $this->variance !== 0;
    }

    public function isOverstock(): bool
    {
        return $this->variance > 0;
    }

    public function isShortage(): bool
    {
        return $this->variance < 0;
    }

    public function isMatch(): bool
    {
        return $this->variance === 0;
    }
}
