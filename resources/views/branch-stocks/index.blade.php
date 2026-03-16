@extends('layouts.app')

@section('title', 'Branch Stocks')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Branch Stocks</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Product quantities per branch</p>
            </div>
            <div class="flex items-center gap-3">
                @if (auth()->user()?->hasPermission('stock-management.view'))
                    <form method="POST" action="{{ route('stock-management.reconciliation.fix') }}" class="inline"
                        onsubmit="return confirm('Run reconciliation for all branches? This will fix movement balances for today.');">
                        @csrf
                        <input type="hidden" name="date" value="today">
                        <button type="submit"
                            class="bg-amber-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-600 transition shadow-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            <span>Reconcile</span>
                        </button>
                    </form>
                @endif
                @if (auth()->user()?->hasPermission('branch-stocks.create'))
                    <a href="{{ route('branch-stocks.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Stock</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Items</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total_items'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Quantity</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total_quantity'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Low Stock</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['low_stock'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Out of Stock</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['out_of_stock'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('branch-stocks.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-48">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All branches</option>
                        @foreach ($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search product</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Product name or SKU..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-56">
                    <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                    <select id="product_id" name="product_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All products</option>
                        @foreach ($products ?? [] as $product)
                            <option value="{{ $product->id }}"
                                {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}
                                ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request()->hasAny(['branch_id', 'search', 'product_id']))
                    <a href="{{ route('branch-stocks.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($stocks as $s)
                    <div class="px-4 py-4 hover:bg-themeInput/50">
                        <div class="text-sm font-semibold text-primary">{{ $s->branch->name ?? '—' }}</div>
                        <div class="text-xs text-themeBody">{{ $s->product->name ?? '—' }} · Qty {{ $s->display_quantity ?? 0 }}
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted">No branch stock found.</div>
                @endforelse
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Available</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Minimum Level</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($stocks as $stock)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $stock->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $stock->product->name }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $stock->product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $stock->display_quantity }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-primary">{{ $stock->available_quantity }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $stock->product->minimum_stock_level ?? 10 }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $minimumLevel = $stock->product->minimum_stock_level ?? 10;
                                        $isLowStock = $stock->display_quantity > 0 && $stock->display_quantity <= $minimumLevel;
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $isLowStock ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $isLowStock ? 'Low Stock' : 'In Stock' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button @click="open = !open" x-ref="button"
                                            class="text-themeBody hover:text-themeHeading focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            <div class="py-1">
                                                @if (auth()->user()?->hasPermission('branch-stocks.update'))
                                                    <a href="{{ route('branch-stocks.edit', $stock) }}"
                                                        class="block px-4 py-2 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
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
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No stock
                                    records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stocks->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $stocks->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
