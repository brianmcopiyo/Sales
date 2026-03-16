@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('catalog.index'), 'label' => 'Back to Catalog'])
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Products</h1>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('products.view'))
                    <a href="{{ route('products.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('products.update'))
                    <form method="GET" action="{{ route('products.merge.form') }}" id="products-merge-form"
                        class="inline-flex">
                        <button type="submit" id="merge-selected-btn" disabled
                            class="merge-btn-state inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm cursor-not-allowed opacity-60"
                            title="Select 2 or more products to merge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <span>Merge selected</span>
                        </button>
                    </form>
                @endif
                @if (auth()->user()?->hasPermission('products.create'))
                    <a href="{{ route('products.create') }}"
                        class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Product</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('products.index') }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('status') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="products-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Products</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $stats['total'] }}</div>
            </a>
            <a href="{{ route('products.index', ['status' => 'active']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'active' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="products-active">
                <div class="text-sm font-medium text-themeMuted mb-1">Active</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['active'] }}</div>
            </a>
            <a href="{{ route('products.index', ['status' => 'inactive']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'inactive' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="products-inactive">
                <div class="text-sm font-medium text-themeMuted mb-1">Inactive</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $stats['inactive'] }}</div>
            </a>
            <a href="{{ route('products.index', ['status' => 'active', 'low_stock' => '1']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('low_stock') === '1' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="products-low-stock">
                <div class="text-sm font-medium text-themeMuted mb-1">Low Stock</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['low_stock'] }}</div>
            </a>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Name, SKU, or model..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-48">
                    <label for="brand_id" class="block text-sm font-medium text-themeBody mb-2">Brand</label>
                    <select id="brand_id" name="brand_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All brands</option>
                        @foreach ($brands ?? [] as $brand)
                            <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Filter
                </button>
                @if (request()->hasAny(['search', 'brand_id', 'status']))
                    <a href="{{ route('products.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($products as $product)
                    <a href="{{ auth()->user()?->hasPermission('products.view') ? route('products.show', $product) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('products.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary">{{ $product->name }}</div>
                                <div class="text-xs text-themeBody mt-0.5">{{ $product->sku }} · {{ $product->brand->name ?? '—' }}</div>
                                @php $regionalSelling = $product->regionPrices->first()?->selling_price; $licenseCost = $product->license_cost; @endphp
                                <div class="text-xs text-themeMuted mt-1">{{ $regionalSelling !== null ? 'TSh ' . number_format((float)$regionalSelling, 2) : '—' }}</div>
                                @if ($licenseCost !== null && (float)$licenseCost > 0)
                                    <div class="text-xs text-themeBody mt-0.5">License: TSh {{ number_format((float)$licenseCost, 2) }}</div>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium flex-shrink-0 {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No products found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            @if (auth()->user()?->hasPermission('products.update'))
                                <th class="px-4 py-3 text-left w-12">
                                    <span class="sr-only">Select for merge</span>
                                    <input type="checkbox" id="products-select-all" aria-label="Select all for merge"
                                        class="rounded border-themeBorder text-primary focus:ring-primary">
                                </th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                SKU
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Selling Price (My Region)</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                License cost</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder" id="products-table-body">
                        @forelse($products as $product)
                            <tr class="hover:bg-themeHover/50">
                                @if (auth()->user()?->hasPermission('products.update'))
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="ids[]" value="{{ $product->id }}"
                                            form="products-merge-form"
                                            class="product-merge-cb rounded border-themeBorder text-primary focus:ring-primary">
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $product->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $product->brand->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $regionalSelling = $product->regionPrices->first()?->selling_price;
                                    @endphp
                                    <div
                                        class="text-sm font-medium {{ $regionalSelling !== null ? 'text-themeHeading' : 'text-themeMuted' }}">
                                        {{ $regionalSelling !== null ? 'TSh ' . number_format((float) $regionalSelling, 2) : '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php $licenseCost = $product->license_cost; @endphp
                                    <div class="text-sm font-medium {{ $licenseCost !== null && (float)$licenseCost > 0 ? 'text-themeHeading' : 'text-themeMuted' }}">
                                        {{ $licenseCost !== null && (float)$licenseCost > 0 ? 'TSh ' . number_format((float) $licenseCost, 2) : '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button @click="open = !open" x-ref="button"
                                            class="text-themeBody hover:text-themeHeading focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                                </path>
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute right-0 top-full z-[9999] mt-2 w-48 bg-themeCard rounded-xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                                            style="display: none;">
                                            <div class="py-1.5">
                                                @if (auth()->user()?->hasPermission('products.view'))
                                                    <a href="{{ route('products.show', $product) }}"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                                            </path>
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                            </path>
                                                        </svg>
                                                        <span>View</span>
                                                    </a>
                                                @endif
                                                @if (auth()->user()?->hasPermission('products.update'))
                                                    <a href="{{ route('products.merge.form', ['recipient_id' => $product->id]) }}"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                        </svg>
                                                        <span>Merge into this product</span>
                                                    </a>
                                                    <a href="{{ route('products.edit', $product) }}"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                            </path>
                                                        </svg>
                                                        <span>Edit</span>
                                                    </a>
                                                @endif
                                                @if (auth()->user()?->hasPermission('products.delete'))
                                                    <form action="{{ route('products.delete', $product) }}"
                                                        method="POST" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-themeInput transition text-left">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                            <span>Delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->hasPermission('products.update') ? 8 : 7 }}"
                                    class="px-6 py-8 text-center text-themeMuted font-medium">No products
                                    found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    @if (auth()->user()?->hasPermission('products.update'))
        @push('scripts')
            <script>
                (function() {
                    var form = document.getElementById('products-merge-form');
                    var btn = document.getElementById('merge-selected-btn');
                    var selectAll = document.getElementById('products-select-all');
                    var checkboxes = document.querySelectorAll('.product-merge-cb');

                    function updateMergeButton() {
                        var n = document.querySelectorAll('.product-merge-cb:checked').length;
                        if (n >= 2) {
                            btn.disabled = false;
                            btn.classList.remove('cursor-not-allowed', 'opacity-60');
                        } else {
                            btn.disabled = true;
                            btn.classList.add('cursor-not-allowed', 'opacity-60');
                        }
                    }

                    function updateSelectAll() {
                        var total = checkboxes.length;
                        var checked = document.querySelectorAll('.product-merge-cb:checked').length;
                        selectAll.checked = total > 0 && checked === total;
                        selectAll.indeterminate = checked > 0 && checked < total;
                    }
                    form.addEventListener('submit', function(e) {
                        var n = document.querySelectorAll('.product-merge-cb:checked').length;
                        if (n < 2) {
                            e.preventDefault();
                            return false;
                        }
                    });
                    selectAll.addEventListener('change', function() {
                        checkboxes.forEach(function(cb) {
                            cb.checked = selectAll.checked;
                        });
                        updateMergeButton();
                    });
                    checkboxes.forEach(function(cb) {
                        cb.addEventListener('change', function() {
                            updateMergeButton();
                            updateSelectAll();
                        });
                    });
                    updateSelectAll();
                })();
            </script>
        @endpush
    @endif
@endsection
