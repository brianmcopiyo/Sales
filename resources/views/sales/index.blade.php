@extends('layouts.app')

@section('title', 'Sales')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('sales-transactions.index'),
            'label' => 'Back to Sales & Transactions',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Sales</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Record and manage sales</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.export') . (isset($exportQuery) && $exportQuery ? '?' . http_build_query($exportQuery) : '') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export to Excel</span>
                </a>
                @if (auth()->user()?->hasPermission('sales.create'))
                    <a href="{{ route('sales.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Sale</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section: preserve branch= when "All branches" is selected -->
        @php
            $baseQuery = ['branch' => $branchFilter !== null ? $branchFilter : ''];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('sales.index', $baseQuery) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('date_from') && !request('status') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="sales-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Sales</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </a>
            <a href="{{ route('sales.index', array_merge($baseQuery, ['date_from' => today()->format('Y-m-d'), 'date_to' => today()->format('Y-m-d')])) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('date_from') === today()->format('Y-m-d') && request('date_to') === today()->format('Y-m-d') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="sales-today">
                <div class="text-sm font-medium text-themeMuted mb-1">Today</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['today'] }}</div>
            </a>
            <a href="{{ route('sales.index', array_merge($baseQuery, ['date_from' => now()->startOfMonth()->format('Y-m-d')])) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('date_from') === now()->startOfMonth()->format('Y-m-d') && !request('date_to') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="sales-this-month">
                <div class="text-sm font-medium text-themeMuted mb-1">This Month</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['this_month'] }}</div>
            </a>
            <a href="{{ route('sales.index', $baseQuery) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('date_from') && !request('status') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="sales-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Total In Sales</div>
                <div class="text-2xl font-semibold text-amber-600">TSh {{ number_format($stats['total_revenue'], 2) }}</div>
            </a>
            <a href="{{ route('sales.index', $baseQuery) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="sales-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Cost to sell (buying + license + commission + disbursements)</div>
                <div class="text-2xl font-semibold text-themeHeading">TSh {{ number_format($stats['total_cost_to_sell'] ?? 0, 2) }}</div>
            </a>
            <a href="{{ route('sales.index', $baseQuery) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="sales-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Gross profit</div>
                <div class="text-2xl font-semibold text-emerald-600">TSh {{ number_format($stats['total_profit'] ?? 0, 2) }}</div>
            </a>
            <a href="{{ route('sales.index', $baseQuery) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="sales-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Total commission</div>
                <div class="text-2xl font-semibold text-primary">TSh {{ number_format($stats['total_commission'] ?? 0, 2) }}</div>
            </a>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('sales.index') }}" class="flex flex-wrap gap-4 items-end">
                @if (isset($branches) && $branches->isNotEmpty())
                <div class="w-48">
                    <label for="branch" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch" name="branch"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="" {{ $branchFilter === null ? 'selected' : '' }}>All branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchFilter === $b->id ? 'selected' : '' }}>
                                {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Sale #</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Sale number..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-48">
                    <label for="customer_id" class="block text-sm font-medium text-themeBody mb-2">Customer</label>
                    <select id="customer_id" name="customer_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        @foreach ($customers ?? [] as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-36">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request()->hasAny(['search', 'branch', 'customer_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('sales.index', $branchFilter === null ? ['branch' => ''] : []) }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list of cards --}}
            <div class="md:hidden divide-y divide-themeBorder" id="sales-list-mobile">
                @forelse($sales as $sale)
                    @php
                        $devices = $sale->items->pluck('product.name')->filter()->unique()->implode(', ') ?: '—';
                    @endphp
                    <a href="{{ auth()->user()?->hasPermission('sales.view') ? route('sales.show', $sale) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('sales.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary truncate">{{ $sale->sale_number ?? 'Sale' }}</div>
                                <div class="text-sm text-themeBody mt-0.5">{{ $sale->customer?->name ?? '—' }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $sale->branch->name }} · {{ $sale->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-themeMuted mt-0.5 truncate" title="{{ $devices }}">{{ $devices }}</div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm font-semibold text-primary">TSh {{ number_format($sale->total, 2) }}</div>
                                <div class="text-xs text-themeMuted mt-1">
                                    Buy: TSh {{ number_format($sale->total_buying_price, 2) }} · License: TSh {{ number_format($sale->total_license_cost ?? 0, 2) }} · Profit: TSh {{ number_format($sale->gross_profit, 2) }} · Commission: TSh {{ number_format($sale->items->sum('commission_amount'), 2) }}
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium mt-1 {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No sales found.</div>
                @endforelse
            </div>

            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Sold By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Field Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Total</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Buying price</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                License cost</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Profit</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Support</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder" id="sales-table-body">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $sale->customer?->name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $sale->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $sale->soldBy->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $sale->items->first()?->fieldAgent?->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody max-w-[160px] truncate"
                                        title="{{ $sale->items->pluck('product.name')->filter()->unique()->implode(', ') ?: '—' }}">
                                        {{ $sale->items->pluck('product.name')->filter()->unique()->implode(', ') ?: '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody max-w-[120px] truncate"
                                        title="{{ $sale->items->pluck('product.brand.name')->filter()->unique()->implode(', ') ?: '—' }}">
                                        {{ $sale->items->pluck('product.brand.name')->filter()->unique()->implode(', ') ?: '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-primary">TSh
                                        {{ number_format($sale->total, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">TSh
                                        {{ number_format($sale->total_buying_price, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">TSh
                                        {{ number_format($sale->total_license_cost ?? 0, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-emerald-600">TSh
                                        {{ number_format($sale->gross_profit, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-primary">TSh
                                        {{ number_format($sale->items->sum('commission_amount'), 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-amber-600">
                                        TSh {{ number_format($sale->customer_disbursements_sum_amount ?? 0, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $sale->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
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
                                            <div class="py-1">
                                                @if (auth()->user()?->hasPermission('sales.view'))
                                                    <a href="{{ route('sales.show', $sale) }}"
                                                        class="block px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
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
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="px-6 py-12 text-center text-themeMuted font-medium">No sales
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($sales->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
