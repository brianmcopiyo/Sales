<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Outlet extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'lat',
        'lng',
        'contact_name',
        'contact_phone',
        'contact_email',
        'branch_id',
        'region_id',
        'assigned_to',
        'geo_fence_type',
        'geo_fence_radius_metres',
        'geo_fence_polygon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'geo_fence_polygon' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    public function plannedVisits()
    {
        return $this->hasMany(PlannedVisit::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /** Outlet types for dropdowns */
    public static function types(): array
    {
        return [
            'retail' => 'Retail',
            'kiosk' => 'Kiosk',
            'dealer' => 'Dealer',
            'wholesale' => 'Wholesale',
            'other' => 'Other',
        ];
    }
}
