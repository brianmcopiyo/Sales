<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class DeviceStatusLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'device_id',
        'status',
        'performed_by_user_id',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
