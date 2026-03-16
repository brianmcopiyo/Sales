<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class SaleItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'sale_id',
        'product_id',
        'field_agent_id',
        'quantity',
        'unit_price',
        'unit_license_cost',
        'subtotal',
        'commission_per_device',
        'commission_amount',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'unit_license_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'commission_per_device' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Seller user (field_agent_id stores users.id – commission is tied to user, not field agent). */
    public function fieldAgent()
    {
        return $this->belongsTo(User::class, 'field_agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'field_agent_id');
    }

    /**
     * Total license cost (cost to sell) for this line: quantity × unit_license_cost.
     */
    public function getTotalLicenseCostAttribute(): float
    {
        return (float) ($this->quantity * ($this->unit_license_cost ?? 0));
    }
}

