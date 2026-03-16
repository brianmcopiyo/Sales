<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class RecurringBill extends Model
{
    use HasFactory, HasUuid;

    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_YEARLY = 'yearly';

    protected $table = 'recurring_bills';

    protected $fillable = [
        'vendor_id',
        'branch_id',
        'category_id',
        'amount',
        'description',
        'frequency',
        'next_due_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(BillCategory::class, 'category_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'recurring_bill_id');
    }

    /**
     * Advance next_due_date by one period based on frequency.
     */
    public function advanceNextDueDate(): void
    {
        $date = $this->next_due_date->copy();
        $this->next_due_date = match ($this->frequency) {
            self::FREQUENCY_MONTHLY => $date->addMonth(),
            self::FREQUENCY_QUARTERLY => $date->addMonths(3),
            self::FREQUENCY_YEARLY => $date->addYear(),
            default => $date->addMonth(),
        };
        $this->save();
    }
}
