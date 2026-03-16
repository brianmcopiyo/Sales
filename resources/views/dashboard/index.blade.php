@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        canvas {
            max-height: 100% !important;
            height: 100% !important;
        }
    </style>
    <div class="space-y-8">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div class="flex items-center gap-4 flex-wrap">
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Dashboard</h1>
                <form method="get" action="{{ route('dashboard') }}" class="flex items-center gap-4 flex-wrap">
                    @if ($branchHasDescendants ?? false)
                        <input type="hidden" name="include_descendants" value="{{ ($includeDescendants ?? true) ? '1' : '0' }}">
                    @endif
                    <div class="flex items-center gap-2">
                        <label for="period" class="text-sm text-themeMuted font-medium whitespace-nowrap">Summary:</label>
                        <select name="period" id="period" onchange="this.form.submit()"
                            class="text-sm rounded-lg border border-themeBorder bg-themeInput/50 text-themeBody focus:ring-2 focus:ring-primary focus:border-primary py-1.5 pl-2 pr-8">
                            @foreach($periodOptions ?? [] as $value => $label)
                                <option value="{{ $value }}" {{ ($period ?? 'this_month') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($branchHasDescendants ?? false)
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-themeMuted font-medium whitespace-nowrap">Scope:</span>
                            <div class="flex rounded-lg border border-themeBorder overflow-hidden">
                                <a href="{{ route('dashboard', ['period' => $period ?? 'this_month', 'include_descendants' => '0']) }}"
                                    class="px-3 py-1.5 text-sm font-medium transition {{ !($includeDescendants ?? true) ? 'bg-primary text-white' : 'bg-themeInput/50 text-themeBody hover:bg-themeHover' }}">This branch only</a>
                                <a href="{{ route('dashboard', ['period' => $period ?? 'this_month', 'include_descendants' => '1']) }}"
                                    class="px-3 py-1.5 text-sm font-medium transition {{ ($includeDescendants ?? true) ? 'bg-primary text-white' : 'bg-themeInput/50 text-themeBody hover:bg-themeHover' }}">Branch + sub-branches</a>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if ($canViewBills ?? false)
                    <a href="{{ route('bills.index') }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Bills</span>
                    </a>
                    @if (auth()->user()?->hasPermission('bills.create'))
                        <a href="{{ route('bills.create') }}"
                            class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>New bill</span>
                        </a>
                    @endif
                @endif
                @if ($canAccessRestockWizard ?? false)
                    <a href="{{ route('stock-management.restock-wizard') }}"
                        class="inline-flex items-center gap-2 bg-amber-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-600 transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                        <span>Create new stock (wizard)</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($canViewSales ?? false)
            {{-- Sales Overview: all metrics for the selected summary period only --}}
            <h2 class="text-xl font-semibold text-primary tracking-tight mb-4">Sales Overview</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Sales ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-themeHeading mt-0.5">{{ number_format($stats['sales_in_period'] ?? 0) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Completed ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-emerald-600 mt-0.5">{{ number_format($stats['sales_completed_in_period'] ?? 0) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Pending ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-amber-600 mt-0.5">{{ number_format($stats['sales_pending_in_period'] ?? 0) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Revenue ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-themeHeading mt-0.5">TSh {{ number_format($stats['revenue_in_period'] ?? 0, 2) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Cost to sell ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-xs text-themeMuted mt-0.5">buying + license + commission + support, petty cash & bills</div>
                    <div class="text-base font-semibold text-themeHeading mt-0.5">TSh {{ number_format($stats['cost_to_sell_in_period'] ?? 0, 2) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Gross profit ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-xs text-themeMuted mt-0.5">after petty cash & bills</div>
                    <div class="text-base font-semibold text-emerald-600 mt-0.5">TSh {{ number_format($stats['profit_in_period'] ?? 0, 2) }}</div>
                </div>
                @if ($canViewCustomerDisbursements ?? false)
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Support ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-themeHeading mt-0.5">TSh {{ number_format($stats['support_in_period'] ?? 0, 2) }}</div>
                </div>
                @endif
                @if ($canViewCommissions ?? false)
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Commission ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-primary mt-0.5">TSh {{ number_format($stats['commission_in_period'] ?? 0, 2) }}</div>
                </div>
                @endif
            </div>

            @endif

            @if ($canViewPettyCash ?? false)
            {{-- Petty Cash Overview: all metrics for the selected summary period only --}}
            <h2 class="text-xl font-semibold text-primary tracking-tight mb-4">Petty cash overview</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Requests ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-themeHeading mt-0.5">{{ number_format($pettyCashStats['requests_in_period'] ?? 0) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Disbursed ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-themeHeading mt-0.5">{{ number_format($pettyCashStats['disbursed_in_period_count'] ?? 0) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Expenses ({{ $periodLabel ?? 'period' }})</div>
                    <div class="text-base font-semibold text-amber-600 mt-0.5">TSh {{ number_format($pettyCashStats['disbursed_in_period_amount'] ?? 0, 2) }}</div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex items-center justify-end">
                    <a href="{{ route('petty-cash.index') }}" class="text-sm font-medium text-primary hover:underline">View petty cash →</a>
                </div>
            </div>
            @endif

            @if ($canViewBills ?? false)
            {{-- Bills (Accounts Payable) overview --}}
            <h2 class="text-xl font-semibold text-primary tracking-tight mb-4">Bills (Accounts Payable)</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <a href="{{ route('bills.index', ['filter' => 'unpaid']) }}"
                    class="block bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-primary/40 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2 rounded-2xl">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Total unpaid</div>
                    <div class="text-base font-semibold text-primary mt-0.5">TSh {{ number_format($billsStats['total_unpaid'] ?? 0, 2) }}</div>
                </a>
                <a href="{{ route('bills.index', ['filter' => 'due_this_week']) }}"
                    class="block bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-amber-400/50 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:ring-offset-2 rounded-2xl">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Due this week</div>
                    <div class="text-base font-semibold text-amber-600 mt-0.5">{{ number_format($billsStats['due_this_week'] ?? 0) }}</div>
                </a>
                <a href="{{ route('bills.index', ['filter' => 'overdue']) }}"
                    class="block bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-red-400/50 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:ring-offset-2 rounded-2xl">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Overdue</div>
                    <div class="text-base font-semibold text-red-600 mt-0.5">{{ number_format($billsStats['overdue'] ?? 0) }}</div>
                </a>
                <a href="{{ route('bills.index', ['filter' => 'paid_this_month']) }}"
                    class="block bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-emerald-400/50 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400/30 focus:ring-offset-2 rounded-2xl">
                    <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Paid this month</div>
                    <div class="text-base font-semibold text-emerald-600 mt-0.5">TSh {{ number_format($billsStats['paid_this_month'] ?? 0, 2) }}</div>
                </a>
            </div>
            <div class="mb-6">
                <a href="{{ route('bills.index') }}" class="text-sm font-medium text-primary hover:underline">View all bills →</a>
            </div>
            @endif

            @if (($canViewStockManagement ?? false) && ($recent_restock_orders ?? collect())->isNotEmpty())
                <div class="mb-6 bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Most recent restock orders</h2>
                        <a href="{{ route('stock-management.restock-orders.index') }}" class="text-sm font-medium text-primary hover:underline">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-themeBorder">
                            <thead class="bg-themeInput/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Order</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Cost</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-themeBorder">
                                @foreach ($recent_restock_orders as $order)
                                    <tr class="hover:bg-themeInput/50 transition">
                                        <td class="px-6 py-3">
                                            @if (auth()->user()?->hasPermission('stock-management.view'))
                                                <a href="{{ route('stock-management.orders.show', $order) }}" class="text-sm font-medium text-primary hover:underline">{{ $order->display_order_number }}</a>
                                            @else
                                                <span class="text-sm font-medium text-themeHeading">{{ $order->display_order_number }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-themeBody">{{ $order->branch?->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-themeBody">{{ $order->product?->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm font-medium text-themeHeading">{{ $order->quantity_received }}/{{ $order->quantity_ordered }}</td>
                                        <td class="px-6 py-3 text-sm font-medium text-themeBody">TSh {{ number_format((float) ($order->total_acquisition_cost ?? 0), 2) }}</td>
                                        <td class="px-6 py-3 text-sm text-themeMuted">{{ ($order->ordered_at ?? $order->created_at)?->format('M d, Y') }}</td>
                                        <td class="px-6 py-3">
                                            @if ($order->status === 'received_full')
                                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Received</span>
                                            @elseif ($order->status === 'received_partial')
                                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium bg-sky-100 text-sky-800">Partial</span>
                                            @elseif ($order->status === 'cancelled')
                                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium bg-red-100 text-red-800">Cancelled</span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($canViewStockManagement ?? false)
                <div class="mb-6 bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Stocks by dealership</h2>
                        <a href="{{ route('stock-management.restock-orders.index') }}" class="text-sm font-medium text-primary hover:underline">View restock orders</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-themeBorder">
                            <thead class="bg-themeInput/80">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Dealership</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Quantity received</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Orders</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-themeBorder">
                                @forelse ($stocks_by_dealership ?? [] as $row)
                                    <tr class="hover:bg-themeInput/50 transition">
                                        <td class="px-6 py-3 text-sm font-medium text-themeHeading">{{ $row->dealership_label }}</td>
                                        <td class="px-6 py-3 text-sm text-themeBody text-right">{{ number_format((int) $row->quantity_received) }}</td>
                                        <td class="px-6 py-3 text-sm text-themeBody text-right">{{ number_format((int) $row->order_count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-themeMuted text-sm">No restock data by dealership yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($canViewSales ?? false)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Top 5 Performing Users ({{ $periodLabel ?? 'period' }})</h2>
                    <ul class="space-y-3">
                        @forelse($topPerformingUsers ?? [] as $index => $user)
                            <li class="flex items-center justify-between py-2 border-b border-themeBorder last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $index + 1 }}</span>
                                    <a href="{{ route('users.show', $user) }}" class="text-sm font-medium text-themeHeading hover:text-primary">{{ $user->name }}</a>
                                </div>
                                <div class="text-right text-sm">
                                    <span class="font-medium text-themeHeading">{{ number_format($user->sales_count ?? 0) }} sales</span>
                                    <span class="text-themeMuted"> · </span>
                                    <span class="text-emerald-600 font-medium">TSh {{ number_format($user->revenue ?? 0, 2) }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-center text-themeMuted text-sm">No sales in this period</li>
                        @endforelse
                    </ul>
                    @if(($topPerformingUsers ?? collect())->isNotEmpty())
                        <a href="{{ route('sales-stats.index') }}" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">View all stats</a>
                    @endif
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Top 5 Performing Devices ({{ $periodLabel ?? 'period' }})</h2>
                    <ul class="space-y-3">
                        @forelse($topPerformingDevices ?? [] as $index => $item)
                            <li class="flex items-center justify-between py-2 border-b border-themeBorder last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $index + 1 }}</span>
                                    @if($item->product)
                                        <a href="{{ route('products.show', $item->product) }}" class="text-sm font-medium text-themeHeading hover:text-primary">{{ $item->product->name ?? $item->product->sku ?? 'Product' }}</a>
                                    @else
                                        <span class="text-sm font-medium text-themeBody">Product #{{ $item->product_id }}</span>
                                    @endif
                                </div>
                                <div class="text-right text-sm">
                                    <span class="font-medium text-themeHeading">{{ number_format($item->devices_sold ?? 0) }} sold</span>
                                    <span class="text-themeMuted"> · </span>
                                    <span class="text-emerald-600 font-medium">TSh {{ number_format($item->revenue ?? 0, 2) }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-center text-themeMuted text-sm">No device sales in this period</li>
                        @endforelse
                    </ul>
                    @if(($topPerformingDevices ?? collect())->isNotEmpty())
                        <a href="{{ route('sales-stats.index') }}" class="mt-3 inline-block text-sm font-medium text-primary hover:underline">View all stats</a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col" style="height: 340px;">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Sales by Status</h2>
                    <div class="flex-1 min-h-0">
                        <canvas id="salesByStatusChart"></canvas>
                    </div>
                </div>
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col" style="height: 340px;">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Sales & Revenue (Last 6 Months)</h2>
                    <div class="flex-1 min-h-0">
                        <canvas id="monthlySalesRevenueChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Sales</h2>
                    <a href="{{ route('sales.index') }}" class="text-sm font-medium text-primary hover:underline">View all</a>
                </div>
                <div class="overflow-x-auto rounded-xl border border-themeBorder">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Sale</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @forelse($recent_sales as $sale)
                                <tr class="hover:bg-themeHover">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-primary hover:underline">{{ $sale->sale_number }}</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $sale->customer->name ?? '–' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $sale->status }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ number_format($sale->total ?? 0, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-themeMuted">{{ $sale->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-themeMuted font-light">No sales yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Key Metrics Cards (only show cards user has permission to see) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @if (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('stock-management.restock') || auth()->user()?->hasPermission('stock-management.initiate-restock'))
                <a href="{{ route('stock-management.restock-wizard') }}"
                    class="filter-card bg-themeCard rounded-2xl border-2 border-amber-400 border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-amber-500 bg-amber-50/50"
                    data-filter="restock-wizard">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Create new stock</div>
                            <div class="text-lg font-semibold text-themeHeading tracking-tight">Restock wizard</div>
                        </div>
                        <div class="bg-amber-500 rounded-xl p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-themeMuted">
                        Step-by-step order creation
                    </div>
                </a>
            @endif

            @if ($canViewStock ?? false)
                <a href="{{ route('inventory.dashboard') }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                    data-filter="stock-updates">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Total Stock Quantity</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ number_format($stats['total_stock_quantity']) }}</div>
                        </div>
                        <div class="bg-primary/10 rounded-xl p-3">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">Available:
                            {{ number_format($stats['total_available_stock']) }}</span>
                    </div>
                </a>
            @endif

            @if (($canViewProducts ?? false) || ($canViewBranches ?? false))
                <a href="{{ route('products.index') }}"
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Total Products</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ $stats['total_products'] }}
                            </div>
                        </div>
                        <div class="bg-emerald-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['total_branches'] }} Branches</span>
                    </div>
                </a>
            @endif

            @if ($canViewStock ?? false)
                <a href="{{ route('products.index', ['status' => 'active', 'low_stock' => '1']) }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                    data-filter="low-stock">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Low Stock Items</div>
                            <div class="text-2xl font-semibold text-amber-600 tracking-tight">
                                {{ $stats['low_stock_items'] }}
                            </div>
                        </div>
                        <div class="bg-amber-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['out_of_stock_items'] }} Out of Stock</span>
                    </div>
                </a>
            @endif

            @if ($canViewTransfers ?? false)
                <a href="{{ route('stock-transfers.index') }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                    data-filter="transfers">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Pending Transfers</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ $stats['pending_transfers'] }}
                            </div>
                        </div>
                        <div class="bg-sky-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['completed_transfers'] }} {{ $periodLabel ?? 'This month' }}</span>
                    </div>
                </a>
            @endif

            @if ($canViewDevices ?? false)
                <a href="{{ route('devices.index') }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Devices (this branch)</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ $stats['total_devices'] }}
                            </div>
                        </div>
                        <div class="bg-violet-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['available_devices'] }} Available</span>
                    </div>
                </a>
            @endif

            @if ($canViewSales ?? false)
                <a href="{{ route('sales.index') }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Sales (this branch)</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ $stats['total_sales'] }}
                            </div>
                        </div>
                        <div class="bg-emerald-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['sales_in_period'] ?? $stats['sales_this_month'] }} {{ $periodLabel ?? 'This month' }}</span>
                    </div>
                </a>
            @endif
        </div>

        @if ($canViewTickets ?? false)
            <!-- Ticket Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <a href="{{ route('tickets.index') }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('status') && !request('priority') && !request('overdue') ? 'ring-2 ring-primary border-primary' : '' }}"
                    data-filter="tickets-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Total Tickets</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ $stats['total_tickets'] }}</div>
                        </div>
                        <div class="bg-violet-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['open_tickets'] }} Open</span>
                    </div>
                </a>

                <div class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                    data-filter="tickets-in-progress" onclick="filterDashboard('tickets-in-progress')">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">In Progress</div>
                            <div class="text-2xl font-semibold text-sky-600 tracking-tight">
                                {{ $stats['in_progress_tickets'] }}
                            </div>
                        </div>
                        <div class="bg-sky-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['resolved_tickets'] }} Resolved</span>
                    </div>
                </div>

                <a href="{{ route('tickets.index', ['priority' => 'urgent']) }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('priority') === 'urgent' ? 'ring-2 ring-primary border-primary' : '' }}"
                    data-filter="tickets-urgent">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Urgent Tickets</div>
                            <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['urgent_tickets'] }}
                            </div>
                        </div>
                        <div class="bg-red-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">{{ $stats['overdue_tickets'] }} Overdue</span>
                    </div>
                </a>

                <a href="{{ route('tickets.index', ['status' => 'open']) }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'open' ? 'ring-2 ring-primary border-primary' : '' }}"
                    data-filter="tickets-open">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Open Tickets</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ $stats['open_tickets'] }}
                            </div>
                        </div>
                        <div class="bg-emerald-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">Active tickets</span>
                    </div>
                </a>

                <a href="{{ route('tickets.index', ['overdue' => '1']) }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('overdue') === '1' ? 'ring-2 ring-primary border-primary' : '' }}"
                    data-filter="tickets-overdue">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-themeMuted font-medium mb-1 text-sm">Overdue</div>
                            <div class="text-2xl font-semibold text-amber-600 tracking-tight">
                                {{ $stats['overdue_tickets'] }}
                            </div>
                        </div>
                        <div class="bg-amber-100 rounded-xl p-3">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-themeMuted font-medium">Requires attention</span>
                    </div>
                </a>
            </div>
        @endif

        @if (($canViewStock ?? false) || ($canViewTransfers ?? false))
            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if ($canViewStock ?? false)
                    <!-- Stock by Branch Chart -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Stock Distribution by Branch
                        </h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="branchStockChart"></canvas>
                        </div>
                    </div>

                    <!-- Stock by Brand Chart -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Stock by Brand</h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="brandStockChart"></canvas>
                        </div>
                    </div>
                @endif

                @if ($canViewTransfers ?? false)
                    <!-- Stock Movements Chart (Last 30 Days) -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Stock Movements (Last 30 Days)
                        </h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="stockMovementsChart"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if ($canViewTransfers ?? false)
                    <!-- Monthly Stock Movements Trend -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Monthly Stock Movements</h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="monthlyStockMovementsChart"></canvas>
                        </div>
                    </div>

                    <!-- Transfers by Status -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Transfers by Status</h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="transfersStatusChart"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            @if ($canViewStock ?? false)
                <!-- Charts Row 3 (Stock) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Products by Stock -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Top 5 Products by Stock</h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="topStockProductsChart"></canvas>
                        </div>
                    </div>

                    <!-- Stock Status Distribution -->
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                        style="height: 400px;">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Stock Status Distribution</h2>
                        <div class="flex-1 min-h-0">
                            <canvas id="stockStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Stock Information Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if ($canViewTransfers ?? false)
                    <!-- Recent Stock Transfers -->
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Stock Transfers</h2>
                            <a href="{{ route('stock-transfers.index') }}"
                                class="text-sm font-medium text-primary hover:text-primary-dark transition">View All</a>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-themeBorder">
                            <table class="min-w-full divide-y divide-themeBorder">
                                <thead class="bg-themeInput/80">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Product</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            From → To</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Quantity</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-themeCard divide-y divide-themeBorder" id="transfers-table-body">
                                    @forelse($recent_transfers as $transfer)
                                        <tr class="hover:bg-themeHover">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeHeading font-light">
                                                    {{ $transfer->product->name ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeBody font-light">
                                                    {{ $transfer->fromBranch->name ?? '-' }} →
                                                    {{ $transfer->toBranch->name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeHeading font-light font-semibold">
                                                    {{ $transfer->quantity }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span
                                                    class="px-2 py-1 text-xs rounded font-light 
                                        {{ $transfer->status === 'received'
                                            ? 'bg-green-100 text-green-800'
                                            : ($transfer->status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : ($transfer->status === 'in_transit'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : 'bg-themeHover text-themeHeading')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeBody font-light">
                                                    {{ $transfer->created_at->format('M d, Y') }}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-4 text-center text-themeMuted font-light">No
                                                transfers
                                                found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($canViewStock ?? false)
                    <!-- Low Stock Products -->
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-primary tracking-tight">Low Stock Products</h2>
                            <a href="{{ route('products.index') }}"
                                class="text-sm font-medium text-primary hover:text-primary-dark transition">View All</a>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-themeBorder">
                            <table class="min-w-full divide-y divide-themeBorder">
                                <thead class="bg-themeInput/80">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Product</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Branch</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Current</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Minimum</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-themeCard divide-y divide-themeBorder" id="low-stock-table-body">
                                    @forelse($lowStockProducts as $stock)
                                        <tr class="hover:bg-themeHover">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeHeading font-light">{{ $stock->name }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeBody font-light">{{ $stock->branch_name }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-[#E48A22] font-light font-semibold">
                                                    {{ $stock->display_quantity }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-themeBody font-light">
                                                    {{ $stock->minimum_stock_level }}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-4 text-center text-themeMuted font-light">No
                                                low stock
                                                items</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($canViewTickets ?? false)
            <!-- Ticket Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tickets by Status Chart -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                    style="height: 400px;">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Tickets by Status</h2>
                    <div class="flex-1 min-h-0">
                        <canvas id="ticketsByStatusChart"></canvas>
                    </div>
                </div>

                <!-- Tickets by Priority Chart -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] flex flex-col"
                    style="height: 400px;">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Tickets by Priority</h2>
                    <div class="flex-1 min-h-0">
                        <canvas id="ticketsByPriorityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Tickets</h2>
                    <a href="{{ route('tickets.index') }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark transition">View All</a>
                </div>
                <div class="overflow-x-auto rounded-xl border border-themeBorder">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Ticket #</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Subject</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Customer</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Priority</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Assigned To</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder" id="tickets-table-body">
                            @forelse($recent_tickets as $ticket)
                                <tr class="hover:bg-themeHover">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('tickets.show', $ticket) }}"
                                            class="text-sm text-primary hover:text-primary-dark font-light">
                                            {{ $ticket->ticket_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-themeHeading font-light">
                                            {{ \Illuminate\Support\Str::limit($ticket->subject, 50) }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-themeBody font-light">
                                            {{ $ticket->customer->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 text-xs rounded font-light 
                                    {{ $ticket->status === 'open'
                                        ? 'bg-green-100 text-green-800'
                                        : ($ticket->status === 'in_progress'
                                            ? 'bg-blue-100 text-blue-800'
                                            : ($ticket->status === 'resolved'
                                                ? 'bg-themeHover text-themeHeading'
                                                : 'bg-red-100 text-red-800')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 text-xs rounded font-light 
                                    {{ $ticket->priority === 'urgent'
                                        ? 'bg-red-100 text-red-800'
                                        : ($ticket->priority === 'high'
                                            ? 'bg-orange-100 text-orange-800'
                                            : ($ticket->priority === 'medium'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-blue-100 text-blue-800')) }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-themeBody font-light">
                                            {{ $ticket->assignedTo->name ?? 'Unassigned' }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-themeBody font-light">
                                            {{ $ticket->created_at->format('M d, Y') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-themeMuted font-light">No tickets
                                        found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($canViewStock ?? false)
            <!-- Recent Stock Updates -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Stock Updates</h2>
                </div>
                <div class="overflow-x-auto rounded-xl border border-themeBorder">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Branch</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Available</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Reserved</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Last
                                    Updated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder" id="stock-updates-table-body">
                            @forelse($recent_stock_updates as $stock)
                                <tr class="hover:bg-themeHover">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-themeHeading font-light">
                                            {{ $stock->product->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-themeBody font-light">{{ $stock->branch->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-themeHeading font-light font-semibold">
                                            {{ $stock->display_quantity }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-green-600 font-light">{{ $stock->available_quantity }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-orange-600 font-light">{{ $stock->reserved_quantity }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-themeBody font-light">
                                            {{ $stock->updated_at->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs text-themeMuted font-light">
                                            {{ $stock->updated_at->format('h:i A') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-themeMuted font-light">No stock
                                        updates
                                        found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($canViewDevices ?? false)
            <!-- Recent Devices (this branch) -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Devices</h2>
                    <a href="{{ route('devices.index') }}" class="text-sm font-medium text-primary hover:underline">View
                        all</a>
                </div>
                <div class="overflow-x-auto rounded-xl border border-themeBorder">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    IMEI</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Updated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @forelse($recent_devices as $device)
                                <tr class="hover:bg-themeHover">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-themeHeading">
                                        {{ $device->imei }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">
                                        {{ $device->product->name ?? '–' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 text-xs font-medium rounded-lg
                                            @if ($device->status === 'available') bg-emerald-100 text-emerald-800
                                            @elseif ($device->status === 'assigned') bg-sky-100 text-sky-800
                                            @else bg-amber-100 text-amber-800 @endif">
                                            {{ ucfirst($device->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-themeMuted">
                                        {{ $device->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-themeMuted font-light">No devices
                                        in this branch</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        // Chart colors
        const primaryColor = '#006F78';
        const secondaryColor = '#E48A22';
        const successColor = '#10B981';
        const infoColor = '#3B82F6';
        const warningColor = '#F59E0B';
        const dangerColor = '#EF4444';

        // Stock by Branch Chart (Bar Chart) – only if element exists (user has permission)
        const branchStockEl = document.getElementById('branchStockChart');
        if (branchStockEl) {
            const branchStockCtx = branchStockEl.getContext('2d');
            new Chart(branchStockCtx, {
                type: 'bar',
                data: {
                    labels: @json($branchStockLabels),
                    datasets: [{
                        label: 'Stock Quantity',
                        data: @json($branchStockData),
                        backgroundColor: primaryColor + '80',
                        borderColor: primaryColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Stock by Brand Chart
        const brandStockEl = document.getElementById('brandStockChart');
        if (brandStockEl) {
            const brandStockCtx = brandStockEl.getContext('2d');
            new Chart(brandStockCtx, {
                type: 'bar',
                data: {
                    labels: @json($brandStockLabels ?? []),
                    datasets: [{
                        label: 'Stock Quantity',
                        data: @json($brandStockData ?? []),
                        backgroundColor: secondaryColor + '80',
                        borderColor: secondaryColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Stock Movements Chart (Line Chart)
        const stockMovementsEl = document.getElementById('stockMovementsChart');
        if (stockMovementsEl) {
            const stockMovementsCtx = stockMovementsEl.getContext('2d');
            new Chart(stockMovementsCtx, {
                type: 'line',
                data: {
                    labels: @json($stockMovementsLabels),
                    datasets: [{
                        label: 'Units Moved',
                        data: @json($stockMovementsData),
                        borderColor: infoColor,
                        backgroundColor: infoColor + '20',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Monthly Stock Movements Chart (Line Chart)
        const monthlyStockMovementsEl = document.getElementById('monthlyStockMovementsChart');
        if (monthlyStockMovementsEl) {
            const monthlyStockMovementsCtx = monthlyStockMovementsEl.getContext('2d');
            new Chart(monthlyStockMovementsCtx, {
                type: 'line',
                data: {
                    labels: @json($monthlyStockLabels),
                    datasets: [{
                        label: 'Units Moved',
                        data: @json($monthlyStockMovements),
                        borderColor: successColor,
                        backgroundColor: successColor + '20',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Transfers by Status (Doughnut Chart)
        const transfersStatusEl = document.getElementById('transfersStatusChart');
        if (transfersStatusEl) {
            const transfersStatusData = @json($transfersByStatus);
            const transfersStatusLabels = Object.keys(transfersStatusData);
            const transfersStatusValues = Object.values(transfersStatusData);
            const transfersStatusColors = [
                successColor, // received
                warningColor, // pending
                infoColor, // in_transit
                dangerColor // cancelled
            ];

            const transfersStatusCtx = transfersStatusEl.getContext('2d');
            new Chart(transfersStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: transfersStatusLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)
                        .replace(
                            '_', ' ')),
                    datasets: [{
                        data: transfersStatusValues,
                        backgroundColor: transfersStatusColors.slice(0, transfersStatusLabels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                        }
                    }
                }
            });
        }

        // Top Products by Stock Chart (Horizontal Bar Chart)
        const topStockProductsEl = document.getElementById('topStockProductsChart');
        if (topStockProductsEl) {
            const topStockProductsData = @json($topStockProducts);
            const topStockProductsCtx = topStockProductsEl.getContext('2d');
            new Chart(topStockProductsCtx, {
                type: 'bar',
                data: {
                    labels: topStockProductsData.map(item => item.name),
                    datasets: [{
                        label: 'Stock Quantity',
                        data: topStockProductsData.map(item => item.total_stock),
                        backgroundColor: successColor + '80',
                        borderColor: successColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Stock Status Distribution (Pie Chart)
        const stockStatusEl = document.getElementById('stockStatusChart');
        if (stockStatusEl) {
            const stockStatusData = @json($stockStatusDistribution);
            const stockStatusLabels = Object.keys(stockStatusData);
            const stockStatusValues = Object.values(stockStatusData);
            const stockStatusColors = [
                warningColor, // low
                infoColor, // medium
                successColor // high
            ];

            const stockStatusCtx = stockStatusEl.getContext('2d');
            new Chart(stockStatusCtx, {
                type: 'pie',
                data: {
                    labels: stockStatusLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1) +
                        ' Stock'),
                    datasets: [{
                        data: stockStatusValues,
                        backgroundColor: stockStatusColors.slice(0, stockStatusLabels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                        }
                    }
                }
            });
        }

        // Tickets by Status Chart (Bar Chart)
        const ticketsByStatusEl = document.getElementById('ticketsByStatusChart');
        if (ticketsByStatusEl) {
            const ticketsByStatusData = @json($ticketsByStatus);
            const ticketsByStatusLabels = Object.keys(ticketsByStatusData);
            const ticketsByStatusValues = Object.values(ticketsByStatusData);
            const ticketsByStatusColors = {
                'open': successColor,
                'in_progress': infoColor,
                'resolved': warningColor,
                'closed': dangerColor
            };

            const ticketsByStatusCtx = ticketsByStatusEl.getContext('2d');
            new Chart(ticketsByStatusCtx, {
                type: 'bar',
                data: {
                    labels: ticketsByStatusLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)
                        .replace(
                            '_', ' ')),
                    datasets: [{
                        label: 'Tickets',
                        data: ticketsByStatusValues,
                        backgroundColor: ticketsByStatusLabels.map(label => ticketsByStatusColors[label] ||
                            primaryColor + '80'),
                        borderColor: ticketsByStatusLabels.map(label => ticketsByStatusColors[label] ||
                            primaryColor),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Tickets by Priority Chart (Bar Chart)
        const ticketsByPriorityEl = document.getElementById('ticketsByPriorityChart');
        if (ticketsByPriorityEl) {
            const ticketsByPriorityData = @json($ticketsByPriority);
            const ticketsByPriorityLabels = Object.keys(ticketsByPriorityData);
            const ticketsByPriorityValues = Object.values(ticketsByPriorityData);
            const ticketsByPriorityColors = {
                'urgent': dangerColor,
                'high': warningColor,
                'medium': infoColor,
                'low': successColor
            };

            const ticketsByPriorityCtx = ticketsByPriorityEl.getContext('2d');
            new Chart(ticketsByPriorityCtx, {
                type: 'bar',
                data: {
                    labels: ticketsByPriorityLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
                    datasets: [{
                        label: 'Tickets',
                        data: ticketsByPriorityValues,
                        backgroundColor: ticketsByPriorityLabels.map(label => ticketsByPriorityColors[
                                label] ||
                            primaryColor + '80'),
                        borderColor: ticketsByPriorityLabels.map(label => ticketsByPriorityColors[label] ||
                            primaryColor),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        @if ($canViewSales ?? false)
        // Sales by Status (only output when user can view sales)
        const salesByStatusEl = document.getElementById('salesByStatusChart');
        if (salesByStatusEl) {
            const salesByStatusData = @json($salesByStatus ?? []);
            const salesByStatusLabels = Object.keys(salesByStatusData);
            const salesByStatusValues = Object.values(salesByStatusData);
            const salesByStatusColors = [successColor, warningColor, infoColor, dangerColor, primaryColor];
            const salesByStatusCtx = salesByStatusEl.getContext('2d');
            new Chart(salesByStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: salesByStatusLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1).replace('_', ' ')),
                    datasets: [{
                        data: salesByStatusValues,
                        backgroundColor: salesByStatusColors.slice(0, salesByStatusLabels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: true, position: 'right' } }
                }
            });
        }

        // Monthly Sales & Revenue (last 6 months)
        const monthlySalesRevenueEl = document.getElementById('monthlySalesRevenueChart');
        if (monthlySalesRevenueEl) {
            const monthlySalesLabels = @json($monthlySalesLabels ?? []);
            const monthlySalesData = @json($monthlySalesData ?? []);
            const monthlyRevenueData = @json($monthlyRevenueData ?? []);
            const monthlySalesRevenueCtx = monthlySalesRevenueEl.getContext('2d');
            new Chart(monthlySalesRevenueCtx, {
                type: 'bar',
                data: {
                    labels: monthlySalesLabels,
                    datasets: [
                        { label: 'Sales (count)', data: monthlySalesData, backgroundColor: primaryColor + '99', borderColor: primaryColor, borderWidth: 1, yAxisID: 'y' },
                        { label: 'Revenue', data: monthlyRevenueData, backgroundColor: secondaryColor + '99', borderColor: secondaryColor, borderWidth: 1, yAxisID: 'y1' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: { beginAtZero: true, position: 'left', ticks: { stepSize: 1 } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                    },
                    plugins: { legend: { display: true } }
                }
            });
        }
        @endif
    </script>
@endsection
