<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class DistributorTarget extends Model
{
    use HasUuid;

    const TARGET_TYPES = [
        'revenue'          => 'Revenue',
        'quantity'         => 'Quantity',
        'outlet_coverage'  => 'Outlet Coverage',
    ];

    const PERIOD_TYPES = [
        'monthly'   => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly'    => 'Yearly',
    ];

    protected $fillable = [
        'distributor_profile_id',
        'target_type',
        'period_type',
        'period_year',
        'period_value',
        'target_value',
        'set_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'period_year'  => 'integer',
            'period_value' => 'integer',
        ];
    }

    public function distributorProfile()
    {
        return $this->belongsTo(DistributorProfile::class);
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function progressPercent(float $actual): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }
        return min(100, round(($actual / $this->target_value) * 100, 1));
    }

    public function getPeriodLabel(): string
    {
        if ($this->period_type === 'monthly') {
            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $month = $months[($this->period_value - 1)] ?? $this->period_value;
            return "{$month} {$this->period_year}";
        }
        if ($this->period_type === 'quarterly') {
            return "Q{$this->period_value} {$this->period_year}";
        }
        return (string) $this->period_year;
    }
}
