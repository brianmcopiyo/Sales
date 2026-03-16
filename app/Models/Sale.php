<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use App\Models\Device;
use App\Services\InventoryMovementService;

class Sale extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'sale_number',
        'branch_id',
        'customer_id',
        'sold_by',
        'subtotal',
        'tax',
        'discount',
        'total',
        'total_license_cost',
        'status',
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
        ];
    }

    /**
     * Total cost to sell = buying price + license cost + commission + customer disbursements (support).
     */
    public function getTotalCostToSellAttribute(): float
    {
        $buyingPrice = (float) $this->total_buying_price;
        $license = (float) ($this->total_license_cost ?? 0);
        $commission = (float) ($this->relationLoaded('items')
            ? $this->items->sum('commission_amount')
            : $this->items()->sum('commission_amount'));
        $disbursements = (float) ($this->relationLoaded('customerDisbursements')
            ? $this->customerDisbursements->sum('amount')
            : $this->customerDisbursements()->sum('amount'));
        return $buyingPrice + $license + $commission + $disbursements;
    }

    /**
     * Gross profit = total revenue minus cost to sell (buying + license + commission + support).
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

    public function customerDisbursements()
    {
        return $this->hasMany(CustomerDisbursement::class);
    }

    /**
     * Whether this sale has any disbursement request that is still pending approval.
     * Sales requiring disbursement remain pending until the disbursement is approved.
     */
    public function hasPendingDisbursement(): bool
    {
        return $this->customerDisbursements()->where('status', CustomerDisbursement::STATUS_PENDING)->exists();
    }

    /**
     * When a sale is cancelled: free all devices (IMEIs) linked to this sale so they can be sold again.
     * Resets device sale_id, customer_id, status to available; restores branch stock; records inventory movement.
     */
    public function freeDevicesForResale(?string $userId = null): void
    {
        $devices = Device::where('sale_id', $this->id)->get();
        $userId = $userId ?? auth()->id();

        foreach ($devices as $device) {
            $branchId = $device->branch_id;
            $productId = $device->product_id;

            $device->update([
                'sale_id' => null,
                'customer_id' => null,
                'status' => 'available',
                'sold_by_user_id' => null,
                'has_received_disbursement' => false,
            ]);

            InventoryMovementService::recordSaleCancellation(
                $branchId,
                $productId,
                1,
                $this->id,
                $userId
            );
        }
    }

    public function evidence()
    {
        return $this->hasMany(SaleAttachment::class, 'sale_id');
    }

    public function deviceReplacements()
    {
        return $this->hasMany(DeviceReplacement::class);
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
