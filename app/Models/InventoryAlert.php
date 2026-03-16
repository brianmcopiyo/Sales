<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class InventoryAlert extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'branch_id',
        'product_id',
        'alert_type',
        'threshold_value',
        'current_value',
        'is_resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'integer',
            'current_value' => 'integer',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
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

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Helper methods
    public function resolve(?string $userId = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }

    public function isLowStock(): bool
    {
        return $this->alert_type === 'low_stock';
    }

    public function isOutOfStock(): bool
    {
        return $this->alert_type === 'out_of_stock';
    }

    public function isHighVariance(): bool
    {
        return $this->alert_type === 'high_variance';
    }
}
