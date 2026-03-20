<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Scheme;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SchemeService
{
    /**
     * Get all active schemes applicable to this sale, based on date, region, outlet type,
     * minimum order amount, and minimum quantity.
     */
    public function getApplicableSchemes(Sale $sale): Collection
    {
        $today = Carbon::today();
        $outletType = $sale->outlet?->type;
        $regionId = $sale->branch?->region_id;
        $totalQuantity = $sale->items->sum('quantity');

        return Scheme::where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(function ($q) use ($regionId) {
                $q->whereNull('region_id')->orWhere('region_id', $regionId);
            })
            ->get()
            ->filter(function (Scheme $scheme) use ($sale, $outletType, $totalQuantity) {
                // Outlet type restriction
                $allowedTypes = $scheme->applies_to_outlet_types;
                if (!empty($allowedTypes) && $outletType && !in_array($outletType, $allowedTypes, true)) {
                    return false;
                }
                // Minimum order amount
                if ($scheme->min_order_amount !== null && (float) $sale->subtotal < (float) $scheme->min_order_amount) {
                    return false;
                }
                // Minimum quantity
                if ($scheme->min_quantity !== null && $totalQuantity < $scheme->min_quantity) {
                    return false;
                }
                return true;
            });
    }

    /**
     * Pick the scheme yielding the highest discount and return the result.
     *
     * @return array{discount: float, scheme_id: string|null, description: string}
     */
    public function applyBestScheme(Sale $sale): array
    {
        $schemes = $this->getApplicableSchemes($sale);

        if ($schemes->isEmpty()) {
            return ['discount' => 0.0, 'scheme_id' => null, 'description' => 'No applicable scheme'];
        }

        $best = null;
        $bestDiscount = 0.0;

        foreach ($schemes as $scheme) {
            $discount = $this->calculateDiscount($scheme, $sale);
            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $best = $scheme;
            }
        }

        return [
            'discount'    => $bestDiscount,
            'scheme_id'   => $best?->id,
            'description' => $best ? "{$best->name}: discount of {$bestDiscount}" : 'No improvement',
        ];
    }

    private function calculateDiscount(Scheme $scheme, Sale $sale): float
    {
        return match ($scheme->type) {
            'flat_discount'       => (float) $scheme->value,
            'percentage_discount' => round((float) $sale->subtotal * (float) $scheme->value / 100, 2),
            'buy_x_get_y'         => $this->calculateBuyXGetY($scheme, $sale),
            default               => 0.0,
        };
    }

    private function calculateBuyXGetY(Scheme $scheme, Sale $sale): float
    {
        $totalQty = (int) $sale->items->sum('quantity');
        $buyQty = (int) ($scheme->buy_quantity ?? 1);
        $getQty = (int) ($scheme->get_quantity ?? 1);
        if ($buyQty <= 0) {
            return 0.0;
        }
        $freeUnits = (int) floor($totalQty / $buyQty) * $getQty;
        if ($freeUnits <= 0) {
            return 0.0;
        }
        $minUnitPrice = (float) ($sale->items->min('unit_price') ?? 0);
        return round($freeUnits * $minUnitPrice, 2);
    }
}
