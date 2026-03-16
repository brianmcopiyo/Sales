<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StockTransferItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
        'quantity_received',
    ];

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectiveQuantityReceivedAttribute(): int
    {
        return $this->quantity_received ?? $this->quantity;
    }
}
