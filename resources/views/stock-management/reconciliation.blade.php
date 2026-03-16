@extends('layouts.app')

@section('title', 'Stock Reconciliation')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <a href="{{ route('stock-management.index') }}"
                    class="text-sm font-medium text-primary hover:underline mb-2 inline-block">← Stock Management</a>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Reconciliation</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Step-by-step movement history and discrepancy report</p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('stock-management.reconciliation') }}"
                class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="branch" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select name="branch" id="branch"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}"
                                {{ (string) $branchParam === (string) $b->id ? 'selected' : '' }}>
                                {{ $b->name }} {{ $b->code ? '(' . $b->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="product" class="block text-sm font-medium text-themeBody mb-2">Product (optional)</label>
                    <select name="product" id="product"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Products</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}"
                                {{ (string) $productParam === (string) $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-themeBody mb-2">Date</label>
                    <select name="date" id="date"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $dateFilter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex items-end space-x-2">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        <span>Apply</span>
                    </button>
                    <a href="{{ route('stock-management.reconciliation') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                        <span>Clear</span>
                    </a>
                </div>
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-themeBody">Reconcile stock balances and movements.</p>
            </div>
        </div>

        @if (count($all_discrepancies) > 0)
            <div
                class="bg-themeCard rounded-2xl border border-amber-200 overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-amber-200 bg-amber-50 flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-sm font-semibold text-amber-900">Discrepancies ({{ count($all_discrepancies) }})</h3>
                    @if (in_array($dateFilter, ['today', 'yesterday'], true))
                        <form method="POST" action="{{ route('stock-management.reconciliation.fix') }}"
                            class="inline"
                            onsubmit="return confirm('Fix discrepancies for {{ $dateFilter === 'today' ? 'today' : 'yesterday' }}? This will correct movement balances.');">
                            @csrf
                            <input type="hidden" name="date" value="{{ $dateFilter }}">
                            @if ($branchParam)
                                <input type="hidden" name="branch" value="{{ $branchParam }}">
                            @endif
                            @if ($productParam)
                                <input type="hidden" name="product" value="{{ $productParam }}">
                            @endif
                            <button type="submit"
                                class="bg-amber-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-700 transition shadow-sm flex items-center space-x-2 whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                <span>Fix discrepancies</span>
                            </button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-amber-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-amber-900 uppercase">Branch</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-amber-900 uppercase">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-amber-900 uppercase">Current stock</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-amber-900 uppercase">Expected from movements</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-amber-900 uppercase">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-themeBorder bg-white">
                            @foreach ($all_discrepancies as $d)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="px-4 py-2.5 text-sm font-medium text-themeHeading">{{ $d['branch'] }}</td>
                                    <td class="px-4 py-2.5 text-sm font-medium text-themeHeading">{{ $d['product'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody text-right">{{ $d['current_stock'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody text-right">{{ $d['expected_from_movements'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-amber-800">
                                        @foreach ($d['discrepancies'] as $msg)
                                            <span class="block">{{ $msg }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Step-by-step table -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder">
                <h2 class="text-lg font-semibold text-themeHeading">Step-by-step history</h2>
                <p class="text-xs font-medium text-themeMuted mt-1">
                    @if ($filter_date)
                        Showing movements for {{ $filter_date->toDateString() }} (start-of-day balance = 0).
                    @else
                        Showing all movements (full running balance).
                    @endif
                    @if ($step_rows->total() > 0)
                        — {{ $step_rows->firstItem() }}–{{ $step_rows->lastItem() }} of {{ $step_rows->total() }}
                    @endif
                </p>
            </div>
            @if ($step_rows->total() === 0)
                <div class="p-8 text-center text-themeMuted text-sm">No steps match the current filters.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeHover">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Branch code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Branch</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Product SKU</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Step</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Movement</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Reference</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-themeBody uppercase">Qty before</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-themeBody uppercase">Delta</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-themeBody uppercase">Qty after</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-themeBody uppercase">Expected after</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-themeBody uppercase">OK</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeBody uppercase">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-themeBorder">
                            @php
                                $referenceRouteMap = [
                                    'App\Models\StockTake' => 'stock-takes.show',
                                    'App\Models\StockTransfer' => 'stock-transfers.show',
                                    'App\Models\RestockOrder' => 'stock-management.orders.show',
                                    'App\Models\Sale' => 'sales.show',
                                    'App\Models\StockAdjustment' => 'stock-adjustments.show',
                                ];
                            @endphp
                            @foreach ($step_rows as $row)
                                <tr class="{{ $row['ok'] ? '' : 'bg-red-50' }}">
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['date'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['at'] }}</td>
                                    <td class="px-4 py-2.5 text-sm font-medium text-themeHeading">{{ $row['branch_code'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['branch'] }}</td>
                                    <td class="px-4 py-2.5 text-sm font-medium text-themeHeading">{{ $row['product_sku'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['product'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['step'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['movement_number'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['type'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">
                                        @php
                                            $refUrl = null;
                                            $refType = $row['reference_type'] ?? null;
                                            $refId = $row['reference_id'] ?? null;
                                            if ($refType && $refId && isset($referenceRouteMap[$refType])) {
                                                try {
                                                    $refUrl = route($referenceRouteMap[$refType], $refId);
                                                } catch (\Exception $e) {
                                                    $refUrl = null;
                                                }
                                            }
                                        @endphp
                                        @if ($refUrl)
                                            <a href="{{ $refUrl }}" class="text-primary hover:underline font-medium" title="{{ $row['reference_full'] ?? $row['reference'] }}">{{ $row['reference'] }}</a>
                                        @else
                                            <span title="{{ $row['reference_full'] ?? $row['reference'] }}">{{ $row['reference'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody text-right">{{ $row['quantity_before'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody text-right">{{ $row['quantity_delta'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody text-right">{{ $row['quantity_after'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody text-right">{{ $row['expected_after'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-center">
                                        @if ($row['ok'])
                                            <span class="text-emerald-600 font-medium">Yes</span>
                                        @else
                                            <span class="text-red-600 font-medium">No</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-themeBody">{{ $row['reason'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($step_rows->hasPages())
                    <div class="px-6 py-4 border-t border-themeBorder bg-themeHover">
                        {{ $step_rows->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
