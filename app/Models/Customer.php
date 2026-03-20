<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasUuid;

class Customer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'phone',
        'address',
        'id_number',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if ($customer->branch_id === null && Auth::check() && Auth::user()->branch_id) {
                $customer->branch_id = Auth::user()->branch_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function distributorProfile()
    {
        return $this->hasOne(DistributorProfile::class);
    }

    /**
     * Scope to customers that have at least one sale in the given branch IDs.
     */
    public function scopeVisibleToBranches($query, array $branchIds)
    {
        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }
        return $query->whereHas('sales', fn($s) => $s->whereIn('branch_id', $branchIds));
    }
}
