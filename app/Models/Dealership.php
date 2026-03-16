<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Dealership extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'code',
    ];

    public function restockOrders()
    {
        return $this->hasMany(RestockOrder::class);
    }
}
