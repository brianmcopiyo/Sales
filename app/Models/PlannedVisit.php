<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PlannedVisit extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'planned_date',
        'sequence',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
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
}
