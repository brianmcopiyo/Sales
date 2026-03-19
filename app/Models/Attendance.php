<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'clock_in_at',
        'clock_out_at',
        'lat_in',
        'lng_in',
        'lat_out',
        'lng_out',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
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
}
