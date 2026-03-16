@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('sales-transactions.index'),
            'label' => 'Back to Sales & Transactions',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Transactions</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Sales, disbursements, license, bills, and petty cash</p>
            </div>
            <a href="{{ route('transactions.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Export to Excel</span>
            </a>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Transactions</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Today</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['today'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">This Month</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['this_month'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total In Sales</div>
                <div class="text-2xl font-semibold text-amber-600">TSh {{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Cost to sell</div>
                <div class="text-2xl font-semibold text-themeHeading">TSh {{ number_format($stats['total_cost_to_sell'] ?? 0, 2) }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Gross profit</div>
                <div class="text-2xl font-semibold text-emerald-600">TSh {{ number_format($stats['total_profit'] ?? 0, 2) }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-40">
                    <label for="type" class="block text-sm font-medium text-themeBody mb-2">Type</label>
                    <select id="type" name="type"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="sale" {{ ($typeFilter ?? '') === 'sale' ? 'selected' : '' }}>Sale</option>
                        <option value="disbursement" {{ ($typeFilter ?? '') === 'disbursement' ? 'selected' : '' }}>Disbursement</option>
                        <option value="license" {{ ($typeFilter ?? '') === 'license' ? 'selected' : '' }}>License</option>
                        <option value="bill" {{ ($typeFilter ?? '') === 'bill' ? 'selected' : '' }}>Bill</option>
                        <option value="petty_cash" {{ ($typeFilter ?? '') === 'petty_cash' ? 'selected' : '' }}>Petty cash</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Sale #, invoice, reason..."
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
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
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
                @if (request()->hasAny(['search', 'type', 'customer_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('transactions.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            @php
                $typeLabels = [
                    'sale' => 'Sale',
                    'disbursement' => 'Disbursement',
                    'license' => 'License',
                    'bill' => 'Bill',
                    'petty_cash' => 'Petty cash',
                ];
                $typeBadgeClass = [
                    'sale' => 'bg-emerald-100 text-emerald-800',
                    'disbursement' => 'bg-violet-100 text-violet-800',
                    'license' => 'bg-amber-100 text-amber-800',
                    'bill' => 'bg-slate-100 text-slate-800',
                    'petty_cash' => 'bg-teal-100 text-teal-800',
                ];
            @endphp
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($transactions as $t)
                    @php $tag = $t->type ?? 'sale'; $label = $typeLabels[$tag] ?? ucfirst($tag); $badge = $typeBadgeClass[$tag] ?? 'bg-themeHover text-themeBody'; @endphp
                    @if ($t->url)
                        <a href="{{ $t->url }}" class="block px-4 py-4 hover:bg-themeInput/50 transition-colors">
                    @else
                        <div class="px-4 py-4">
                    @endif
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $badge }}">{{ $label }}</span>
                                <div class="text-sm font-semibold text-primary mt-1">{{ $t->reference }}</div>
                                <div class="text-sm text-themeBody mt-0.5">{{ $t->description }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $t->date?->format('M d, Y h:i A') ?? '—' }}</div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm font-semibold text-themeHeading">TSh {{ number_format($t->amount ?? 0, 2) }}</div>
                                @if ($t->url)
                                    <span class="text-xs text-primary mt-1">View</span>
                                @endif
                            </div>
                        </div>
                    @if ($t->url)
                        </a>
                    @else
                        </div>
                    @endif
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No transactions found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($transactions as $t)
                            @php $tag = $t->type ?? 'sale'; $label = $typeLabels[$tag] ?? ucfirst($tag); $badge = $typeBadgeClass[$tag] ?? 'bg-themeHover text-themeBody'; @endphp
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $badge }}">{{ $label }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $t->reference }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-themeBody max-w-xs truncate" title="{{ $t->description }}">{{ $t->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-semibold text-themeHeading">TSh {{ number_format($t->amount ?? 0, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $t->date?->format('M d, Y') ?? '—' }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $t->date?->format('h:i A') ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($t->url)
                                        <a href="{{ $t->url }}" class="font-medium text-primary hover:text-primary-dark">View details</a>
                                    @else
                                        <span class="text-themeMuted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-themeMuted font-medium">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
