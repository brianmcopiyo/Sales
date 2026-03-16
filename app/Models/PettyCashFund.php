<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PettyCashFund extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'branch_id',
        'custodian_user_id',
        'current_balance',
        'fund_limit',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'fund_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function custodian()
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function requests()
    {
        return $this->hasMany(PettyCashRequest::class, 'petty_cash_fund_id');
    }

    public function replenishments()
    {
        return $this->hasMany(PettyCashReplenishment::class, 'petty_cash_fund_id');
    }
}
