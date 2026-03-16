<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldAgent extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'is_active',
        'total_earned',
        'available_balance',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_earned' => 'decimal:2',
            'available_balance' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'field_agent_id', 'user_id');
    }

    /** Stock allocated to this field agent (by product/branch). */
    public function stockAllocations()
    {
        return $this->hasMany(FieldAgentStock::class, 'field_agent_id', 'user_id');
    }

    /** Stock requests made by this field agent to their branch. */
    public function agentStockRequests()
    {
        return $this->hasMany(AgentStockRequest::class, 'field_agent_id', 'user_id');
    }
}


