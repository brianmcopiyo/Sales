<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Scheme extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'min_quantity',
        'buy_quantity',
        'get_quantity',
        'start_date',
        'end_date',
        'is_active',
        'applies_to_outlet_types',
        'region_id',
    ];

    protected function casts(): array
    {
        return [
            'value'                   => 'decimal:2',
            'min_order_amount'        => 'decimal:2',
            'start_date'              => 'date',
            'end_date'                => 'date',
            'is_active'               => 'boolean',
            'applies_to_outlet_types' => 'array',
        ];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_scheme')
            ->withPivot('discount_applied')
            ->withTimestamps();
    }

    public static function types(): array
    {
        return [
            'flat_discount'       => 'Flat Discount',
            'percentage_discount' => 'Percentage Discount (%)',
            'buy_x_get_y'         => 'Buy X Get Y (free units)',
        ];
    }
}
