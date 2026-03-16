<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PettyCashReplenishment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'petty_cash_fund_id',
        'amount',
        'replenished_by',
        'notes',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function fund()
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function replenishedByUser()
    {
        return $this->belongsTo(User::class, 'replenished_by');
    }
}
