<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class CheckIn extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'check_in_at',
        'check_out_at',
        'lat_in',
        'lng_in',
        'lat_out',
        'lng_out',
        'photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'lat_in' => 'decimal:8',
            'lng_in' => 'decimal:8',
            'lat_out' => 'decimal:8',
            'lng_out' => 'decimal:8',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function auditRuns()
    {
        return $this->hasMany(AuditRun::class);
    }
}
