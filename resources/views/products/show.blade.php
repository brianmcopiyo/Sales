@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $product->name }}</h1>
            <a href="{{ route('products.index') }}"
                class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Product Image Card -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Product Image</h2>
                    @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                            class="w-full rounded-xl border border-themeBorder object-cover">
                    @else
                        <div class="w-full h-64 bg-themeInput rounded-xl border border-themeBorder flex items-center justify-center text-themeMuted font-medium">
                            No image
                        </div>
                    @endif
                </div>

                <!-- Basic Information Card -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">SKU</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $product->sku }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            <span class="px-3 py-1 text-sm font-medium rounded-lg {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if ($product->brand)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Brand</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $product->brand->name }}</div>
                            </div>
                        @endif
                        @if ($product->model)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Model</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $product->model }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Minimum Stock Level</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $product->minimum_stock_level ?? 10 }}</div>
                        </div>
                        @if ($product->license_cost !== null && (float) $product->license_cost > 0)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">License cost (cost to sell per unit)</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $currencySymbol }} {{ number_format((float) $product->license_cost, 2) }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pricing Information Card -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Pricing</h2>
                    <div class="mb-4">
                        <a href="{{ route('product-pricing.edit', $product) }}" class="text-sm font-medium text-primary hover:text-primary-dark transition">Manage Pricing</a>
                    </div>
                    <div class="mt-6">
                        <h3 class="text-base font-semibold text-themeHeading mb-2">Regional Overrides</h3>
                        @if ($product->regionPrices->count() > 0)
                            <div class="overflow-x-auto rounded-xl border border-themeBorder">
                                <table class="min-w-full divide-y divide-themeBorder">
                                    <thead class="bg-themeInput/80">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Region</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Cost Price</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Selling Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                                        @foreach ($product->regionPrices as $rp)
                                            <tr>
                                                <td class="px-4 py-3 text-sm font-medium text-themeHeading">{{ $rp->region?->name ?? $rp->region_id }}</td>
                                                <td class="px-4 py-3 text-sm font-medium text-themeBody">{{ $currencySymbol }} {{ number_format((float) $rp->cost_price, 2) }}</td>
                                                <td class="px-4 py-3 text-sm font-medium text-themeBody">{{ $currencySymbol }} {{ number_format((float) $rp->selling_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-sm font-medium text-themeMuted">No regional price overrides set.</div>
                        @endif
                    </div>
                </div>

                @if ($product->description)
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Description</h2>
                        <div class="text-themeBody font-medium whitespace-pre-wrap">{{ $product->description }}</div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        <a href="{{ route('products.edit', $product) }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <span>Edit Product</span>
                        </a>
                        <a href="{{ route('branch-stocks.index') }}?product={{ $product->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            <span>View Stock Levels</span>
                        </a>
                        <a href="{{ route('stock-transfers.create') }}?product={{ $product->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            <span>Transfer Stock</span>
                        </a>
                        <a href="{{ route('sales.create') }}?product={{ $product->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            <span>Create Sale</span>
                        </a>
                    </div>
                </div>

                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Stock</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ \App\Models\BranchStock::where('product_id', $product->id)->sum('quantity') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branches with Stock</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ \App\Models\BranchStock::where('product_id', $product->id)->where('quantity', '>', 0)->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Sales</div>
                            <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ \App\Models\SaleItem::where('product_id', $product->id)->sum('quantity') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

