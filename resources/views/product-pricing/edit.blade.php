@extends('layouts.app')

@section('title', 'Edit Pricing')

@section('content')
<div class="w-full space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Pricing</h1>
            <div class="text-sm font-medium text-themeMuted mt-1">{{ $product->name }} ({{ $product->sku }})</div>
        </div>
        <a href="{{ route('product-pricing.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </a>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('product-pricing.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-lg font-semibold text-primary tracking-tight mb-2">Regional Pricing</h3>
                <p class="text-sm font-medium text-themeMuted mb-4">Set cost and selling prices per region. Leave both blank to remove pricing for that region.</p>
                <div class="overflow-x-auto border border-themeBorder rounded-xl">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Region</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Cost Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Selling Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Commission/Device</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @foreach($regions as $region)
                                @php
                                    $row = $regionPrices[$region->id] ?? null;
                                @endphp
                                <tr class="hover:bg-themeInput/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-themeBody">{{ $region->name }}</td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0"
                                               name="region_cost_prices[{{ $region->id }}]"
                                               value="{{ old('region_cost_prices.' . $region->id, $row?->cost_price) }}"
                                               class="w-full px-3 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0"
                                               name="region_selling_prices[{{ $region->id }}]"
                                               value="{{ old('region_selling_prices.' . $region->id, $row?->selling_price) }}"
                                               class="w-full px-3 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0"
                                               name="region_commission_per_device[{{ $region->id }}]"
                                               value="{{ old('region_commission_per_device.' . $region->id, $row?->commission_per_device) }}"
                                               placeholder="0"
                                               class="w-full px-3 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Save Pricing</span>
                </button>
                <a href="{{ route('product-pricing.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

