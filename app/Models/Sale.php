<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use App\Services\InventoryMovementService;

class Sale extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'sale_number',
        'branch_id',
        'customer_id',
        'outlet_id',
        'check_in_id',
        'sold_by',
        'subtotal',
        'tax',
        'discount',
        'total',
        'total_license_cost',
        'status',
        'sale_type',
        'notes',
        'returned_at',
        'commission_credited_at',
        'commission_credited_amount',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'commission_credited_at' => 'datetime',
            'commission_credited_amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'total_license_cost' => 'decimal:2',
            'sale_type' => 'string',
        ];
    }

    /**
     * Total cost to sell = buying price + license cost + commission.
     */
    public function getTotalCostToSellAttribute(): float
    {
        $buyingPrice = (float) $this->total_buying_price;
        $license = (float) ($this->total_license_cost ?? 0);
        $commission = (float) ($this->relationLoaded('items')
            ? $this->items->sum('commission_amount')
            : $this->items()->sum('commission_amount'));
        return $buyingPrice + $license + $commission;
    }

    /**
     * Gross profit = total revenue minus cost to sell (buying + license + commission).
     */
    public function getGrossProfitAttribute(): float
    {
        return (float) $this->total - $this->total_cost_to_sell;
    }

    /**
     * Total buying/cost price of items (from product region cost_price). Requires items.product.regionPrices and branch.
     */
    public function getTotalBuyingPriceAttribute(): float
    {
        $regionId = $this->relationLoaded('branch') ? $this->branch?->region_id : null;
        if (!$regionId && $this->branch_id) {
            $this->load('branch');
            $regionId = $this->branch?->region_id;
        }

        return (float) $this->items->sum(function ($item) use ($regionId) {
            $product = $item->relationLoaded('product') ? $item->product : $item->product;
            $cost = $product ? $product->costPriceForRegion($regionId) : null;

            return ($cost ?? 0) * ($item->quantity ?? 1);
        });
    }

    /**
     * Sum of total_buying_price for the given sale IDs (for stats). Uses product region cost_price.
     */
    public static function totalBuyingPriceForSaleIds(array $saleIds): float
    {
        $saleIds = array_filter(array_unique($saleIds));
        if (empty($saleIds)) {
            return 0.0;
        }

        $sales = static::with(['items.product.regionPrices', 'branch'])
            ->whereIn('id', $saleIds)
            ->get();

        return (float) $sales->sum(fn ($sale) => $sale->total_buying_price);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function soldBy()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function evidence()
    {
        return $this->hasMany(SaleAttachment::class, 'sale_id');
    }

    public function schemes()
    {
        return $this->belongsToMany(Scheme::class, 'sale_scheme')
            ->withPivot('discount_applied')
            ->withTimestamps();
    }

    public function scopePrimarySales($query)
    {
        return $query->where('sale_type', 'primary');
    }

    public function scopeSecondarySales($query)
    {
        return $query->where('sale_type', 'secondary');
    }

    /**
     * When a sale is cancelled: return product stock (inventory) for each item.
     */
    public function returnStockOnCancel(?string $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        foreach ($this->items as $item) {
            if ($item->product_id && $this->branch_id) {
                $branchId = $this->branch_id;
                $qty = (int) $item->quantity;
                for ($i = 0; $i < $qty; $i++) {
                    InventoryMovementService::recordSaleCancellation(
                        $branchId,
                        $item->product_id,
                        1,
                        $this->id,
                        $userId
                    );
                }
            }
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = 'SALE-' . strtoupper(uniqid());
            }
        });
    }
}
