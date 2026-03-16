@extends('layouts.app')

@section('title', 'Pricing')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('catalog.index'), 'label' => 'Back to Catalog'])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Pricing</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Regional cost and selling prices by product</p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('product-pricing.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search product</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Product name or SKU..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-48">
                    <label for="region_id" class="block text-sm font-medium text-themeBody mb-2">Region</label>
                    <select id="region_id" name="region_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">Use my branch region</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ ($regionId ?? '') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request()->hasAny(['search', 'region_id']))
                    <a href="{{ route('product-pricing.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Region Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Region Selling</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Commission/Device</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($products as $product)
                            @php
                                $row = $product->regionPrices->first();
                            @endphp
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $product->name }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $product->brand->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-medium {{ $row?->cost_price !== null ? 'text-themeBody' : 'text-themeMuted' }}">
                                        {{ $row?->cost_price !== null ? 'TSh ' . number_format((float) $row->cost_price, 2) : '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-semibold {{ $row?->selling_price !== null ? 'text-primary' : 'text-themeMuted' }}">
                                        {{ $row?->selling_price !== null ? 'TSh ' . number_format((float) $row->selling_price, 2) : '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-medium {{ $row?->commission_per_device !== null ? 'text-themeBody' : 'text-themeMuted' }}">
                                        {{ $row?->commission_per_device !== null ? 'TSh ' . number_format((float) $row->commission_per_device, 2) : '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('product-pricing.edit', $product) }}"
                                        class="bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm inline-flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                        <span>Edit Pricing</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-themeMuted font-medium">No products
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50 rounded-b-2xl border border-t-0 border-themeBorder">
            {{ $products->links() }}
        </div>
    </div>
@endsection

