<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRegionPrice;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductPricingController extends Controller
{
    public function index(Request $request)
    {
        $regionId = $request->get('region_id') ?: auth()->user()?->branch?->region_id;

        $regions = Region::where('is_active', true)->orderBy('name')->get();

        $query = Product::query()->with([
            'brand',
            'regionPrices' => fn($q) => $regionId ? $q->where('region_id', $regionId) : $q->whereRaw('1=0'),
        ]);

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        return view('product-pricing.index', compact('products', 'regions', 'regionId'));
    }

    public function edit(Product $product)
    {
        $product->load('brand');
        $regions = Region::where('is_active', true)->orderBy('name')->get();
        $regionPrices = $product->regionPrices()
            ->get()
            ->keyBy('region_id');

        return view('product-pricing.edit', compact('product', 'regions', 'regionPrices'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'region_cost_prices' => 'nullable|array',
            'region_cost_prices.*' => 'nullable|numeric|min:0',
            'region_selling_prices' => 'nullable|array',
            'region_selling_prices.*' => 'nullable|numeric|min:0',
            'region_commission_per_device' => 'nullable|array',
            'region_commission_per_device.*' => 'nullable|numeric|min:0',
        ]);

        $costs = $validated['region_cost_prices'] ?? [];
        $sellings = $validated['region_selling_prices'] ?? [];
        $commissions = $validated['region_commission_per_device'] ?? [];

        // Enforce pair completeness (either both set or both empty for a region)
        foreach (array_unique(array_merge(array_keys($costs), array_keys($sellings), array_keys($commissions))) as $regionId) {
            $c = $costs[$regionId] ?? null;
            $s = $sellings[$regionId] ?? null;
            $m = $commissions[$regionId] ?? null;

            $cEmpty = ($c === null || $c === '');
            $sEmpty = ($s === null || $s === '');
            $mEmpty = ($m === null || $m === '');

            if ($cEmpty xor $sEmpty) {
                return back()->withErrors([
                    'region_pricing' => 'For each region, cost price and selling price must both be filled (or both left blank).',
                ])->withInput();
            }

            // If pricing row is empty, commission must also be empty (or 0/blank)
            if ($cEmpty && $sEmpty && !$mEmpty) {
                return back()->withErrors([
                    'region_pricing' => 'Commission can only be set for regions that have both cost and selling price.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($product, $costs, $sellings, $commissions) {
            foreach (array_unique(array_merge(array_keys($costs), array_keys($sellings), array_keys($commissions))) as $regionId) {
                $c = $costs[$regionId] ?? null;
                $s = $sellings[$regionId] ?? null;

                if ($c === null || $c === '' || $s === null || $s === '') {
                    ProductRegionPrice::where('product_id', $product->id)
                        ->where('region_id', $regionId)
                        ->delete();
                    continue;
                }

                ProductRegionPrice::updateOrCreate(
                    ['product_id' => $product->id, 'region_id' => $regionId],
                    [
                        'cost_price' => $c,
                        'selling_price' => $s,
                        'commission_per_device' => (float) ($commissions[$regionId] ?? 0),
                    ]
                );
            }
        });

        return redirect()
            ->route('product-pricing.index')
            ->with('success', 'Pricing updated successfully.');
    }
}
