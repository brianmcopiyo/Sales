<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class BranchStock extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
        'reserved_quantity',
        'low_stock_notified_at',
        'zero_stock_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'low_stock_notified_at' => 'datetime',
            'zero_stock_notified_at' => 'datetime',
        ];
    }

    /** Ensure quantity is never stored as negative; stock is either 0 or positive. */
    protected function setQuantityAttribute($value): void
    {
        $this->attributes['quantity'] = max(0, (int) $value);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Quantity for display (stored value is already kept >= 0 by mutator). */
    public function getDisplayQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function isLowStock(): bool
    {
        $minimumLevel = $this->product->minimum_stock_level ?? 10;
        return $this->display_quantity > 0 && $this->display_quantity <= $minimumLevel;
    }
}

