<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Product extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'brand_id',
        'model',
        'minimum_stock_level',
        'license_cost',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock_level' => 'integer',
            'license_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function branchStocks()
    {
        return $this->hasMany(BranchStock::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function regionPrices()
    {
        return $this->hasMany(ProductRegionPrice::class);
    }

    /**
     * Get selling price for a given region.
     */
    public function sellingPriceForRegion(?string $regionId): ?float
    {
        if (!$regionId) {
            return null;
        }

        $price = $this->regionPrices
            ->firstWhere('region_id', $regionId)
            ?->selling_price;

        return $price !== null ? (float) $price : null;
    }

    /**
     * Get cost price for a given region.
     */
    public function costPriceForRegion(?string $regionId): ?float
    {
        if (!$regionId) {
            return null;
        }

        $price = $this->regionPrices
            ->firstWhere('region_id', $regionId)
            ?->cost_price;

        return $price !== null ? (float) $price : null;
    }

    /**
     * Get a display price for the store (first region's selling price, or 0).
     */
    public function getDisplayPrice(): float
    {
        $this->loadMissing('regionPrices');
        $first = $this->regionPrices->first();
        return $first ? (float) $first->selling_price : 0;
    }
}
