<?php

namespace App\Models;

use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DistributorProfile extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'customer_id',
        'assigned_branch_id',
        'credit_limit',
        'outstanding_balance',
        'notes',
        'is_active',
        'portal_enabled_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit'        => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'is_active'           => 'boolean',
            'portal_enabled_at'   => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedBranch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function claims()
    {
        return $this->hasMany(DistributorClaim::class);
    }

    public function targets()
    {
        return $this->hasMany(DistributorTarget::class);
    }

    /** All secondary sales belonging to this distributor's customer. */
    public function sales()
    {
        return $this->customer->sales()->secondarySales();
    }

    /** Revenue for a given period (completed secondary sales). */
    public function getRevenueForPeriod(Carbon $start, Carbon $end): float
    {
        return (float) Sale::where('customer_id', $this->customer_id)
            ->secondarySales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
