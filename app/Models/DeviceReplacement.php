<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class DeviceReplacement extends Model
{
    use HasUuid;

    protected $fillable = [
        'sale_id',
        'original_device_id',
        'replacement_device_id',
        'reason',
        'replaced_by',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function originalDevice()
    {
        return $this->belongsTo(Device::class, 'original_device_id');
    }

    public function replacementDevice()
    {
        return $this->belongsTo(Device::class, 'replacement_device_id');
    }

    public function replacedByUser()
    {
        return $this->belongsTo(User::class, 'replaced_by');
    }
}
