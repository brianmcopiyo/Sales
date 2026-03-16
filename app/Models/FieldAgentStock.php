<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class FieldAgentStock extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'field_agent_stock';

    protected $fillable = [
        'field_agent_id',
        'branch_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function fieldAgent()
    {
        return $this->belongsTo(User::class, 'field_agent_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Whether the agent is running low (at or below product minimum_stock_level). */
    public function isLowStock(): bool
    {
        $minimum = $this->product->minimum_stock_level ?? 1;
        return $this->quantity <= $minimum;
    }

    /** Whether the agent has no stock left. */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
}
