@extends('layouts.app')

@section('title', 'Sales Stats')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('sales-transactions.index'),
            'label' => 'Back to Sales & Transactions',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Sales Stats</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Best performing users and products by sales</p>
            </div>
        </div>

        {{-- Summary stats --}}
        @if (isset($stats))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-1">Completed sales</div>
                    <div class="text-2xl font-semibold text-primary">{{ $stats['completed_sales'] ?? 0 }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-1">Total in sales</div>
                    <div class="text-2xl font-semibold text-amber-600">{{ $currencySymbol }} {{ number_format($stats['total_revenue'] ?? 0, 2) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-1">Cost to sell</div>
                    <div class="text-2xl font-semibold text-themeHeading">{{ $currencySymbol }} {{ number_format($stats['total_cost_to_sell'] ?? 0, 2) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-1">Gross profit</div>
                    <div class="text-2xl font-semibold text-emerald-600">{{ $currencySymbol }} {{ number_format($stats['total_profit'] ?? 0, 2) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-1">Users with sales</div>
                    <div class="text-2xl font-semibold text-emerald-600">{{ $stats['users_with_sales'] ?? 0 }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-1">Units sold</div>
                    <div class="text-2xl font-semibold text-primary">{{ number_format($stats['products_sold'] ?? 0) }}</div>
                </div>
            </div>
        @endif

        @if (isset($branches) && $branches->isNotEmpty())
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <form method="GET" action="{{ route('sales-stats.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div class="w-48">
                        <label for="branch" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                        <select id="branch" name="branch"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All branches</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (isset($branchFilter) && $branchFilter == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                    @if (request()->filled('branch'))
                        <a href="{{ route('sales-stats.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                    @endif
                </form>
            </div>
        @endif

        {{-- Best performing users --}}
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Best performing users (by sales)</h2>
                <p class="text-sm text-themeMuted mt-0.5">Ranked by completed sales revenue (initiated by user)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Completed sales</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Total in sales</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Cost to sell</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Gross profit</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($bestUsers as $index => $u)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $u->name }}</div>
                                    <div class="text-xs text-themeMuted">{{ $u->email ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">{{ $u->completed_sales_count ?? 0 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-amber-600">{{ $currencySymbol }} {{ number_format($u->total_revenue ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($u->cost_to_sell ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-emerald-600">{{ $currencySymbol }} {{ number_format($u->gross_profit ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('sales-stats.user', $u) }}" class="text-primary hover:underline">View details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No sales data for users.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Best performing products --}}
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Best performing products (by sales)</h2>
                <p class="text-sm text-themeMuted mt-0.5">Ranked by revenue from completed sales</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Units sold</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Total in sales</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Cost to sell</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Gross profit</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($bestProducts as $index => $row)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $row->product?->name ?? '—' }}</div>
                                    <div class="text-xs text-themeMuted">{{ $row->product?->sku ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">{{ (int) ($row->total_quantity ?? 0) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-amber-600">{{ $currencySymbol }} {{ number_format($row->total_revenue ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($row->cost_to_sell ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-emerald-600">{{ $currencySymbol }} {{ number_format($row->gross_profit ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($row->product)
                                        <a href="{{ route('sales-stats.product', $row->product) }}" class="text-primary hover:underline">View details</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No sales data for products.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
