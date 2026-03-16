<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StockTake extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'stock_take_number',
        'branch_id',
        'status',
        'stock_take_date',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at',
        'completed_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_take_date' => 'date',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items()
    {
        return $this->hasMany(StockTakeItem::class);
    }

    public function adjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    // Status checks
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'in_progress']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'completed';
    }

    // Auto-generate stock take number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stockTake) {
            if (empty($stockTake->stock_take_number)) {
                $stockTake->stock_take_number = 'ST-' . strtoupper(uniqid());
            }
        });
    }
}
