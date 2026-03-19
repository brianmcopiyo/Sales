<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitRoute extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'territory_id',
        'user_id',
        'route_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'route_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function territory()
    {
        return $this->belongsTo(Territory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlets()
    {
        return $this->belongsToMany(Outlet::class, 'visit_route_outlet')
            ->withPivot(['id', 'sequence'])
            ->withTimestamps()
            ->orderBy('visit_route_outlet.sequence');
    }
}
