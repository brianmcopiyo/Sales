@extends('layouts.app')

@section('title', 'Stock History')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Inventory</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Track historical stock levels and movements</p>
            </div>
        </div>
        @include('inventory._subnav', ['current' => 'history'])

        @if ($movements->total() == 0 && !$branchId && !$productId)
            <div
                class="bg-sky-50 border border-sky-100 rounded-2xl p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-sky-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-sky-900 mb-1">No movements found</h3>
                        <p class="text-xs font-medium text-sky-700">
                            Stock movements are automatically recorded when you:
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li>Restock products</li>
                            <li>Complete sales</li>
                            <li>Transfer stock between branches</li>
                            <li>Approve stock takes</li>
                        </ul>
                        <span class="block mt-2">Note: Movements are only tracked from when the inventory system was
                            implemented. Historical stock activities before this may not appear.</span>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('inventory.stock-history') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select name="branch_id" id="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                    <select name="product_id" id="product_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ $productId == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">From Date (Optional)</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    <p class="text-xs font-medium text-themeMuted mt-1">Leave empty to show all</p>
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">To Date (Optional)</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    <p class="text-xs font-medium text-themeMuted mt-1">Leave empty to show all</p>
                </div>
                <div class="md:col-span-4 flex space-x-2">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('inventory.stock-history') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        <span>Clear</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Movements</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">
                    {{ number_format($stats['total_movements']) }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Increases</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">
                    {{ number_format($stats['total_increases']) }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Decreases</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">
                    {{ number_format($stats['total_decreases']) }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Net Change</div>
                <div
                    class="text-2xl font-semibold {{ $stats['net_change'] >= 0 ? 'text-emerald-600' : 'text-red-600' }} tracking-tight">
                    {{ $stats['net_change'] >= 0 ? '+' : '' }}{{ number_format($stats['net_change']) }}
                </div>
            </div>
        </div>

        <!-- Stock Level Chart (if product and branch selected) -->
        @if ($productId && $branchId && count($stockHistory) > 0)
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Stock Level Over Time</h2>
                </div>
                <div class="h-80">
                    <canvas id="stockLevelChart"></canvas>
                </div>
            </div>
        @endif

        <!-- Current Stock Levels (if filters applied) -->
        @if (($branchId || $productId) && $currentStocks->count() > 0)
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Current Stock Levels</h2>
                </div>
                <div class="overflow-x-auto">
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
                                    Current Stock</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @foreach ($currentStocks as $stock)
                                <tr class="hover:bg-themeInput/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $stock->product->name }}</div>
                                        <div class="text-xs font-medium text-themeMuted">{{ $stock->product->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">{{ $stock->branch->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-themeHeading">
                                            {{ number_format($stock->display_quantity) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($stock->display_quantity == 0)
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-red-100 text-red-800">Out
                                                of Stock</span>
                                        @elseif($stock->isLowStock())
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Low
                                                Stock</span>
                                        @else
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">In
                                                Stock</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Stock History Timeline (if product and branch selected) -->
        @if ($productId && $branchId && count($stockHistory) > 0)
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Stock History Timeline</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach ($stockHistory as $history)
                            <div
                                class="flex items-start space-x-4 p-4 rounded-xl bg-themeInput/80 hover:bg-themeInput transition-colors">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-3 h-3 rounded-full {{ $history['change'] > 0 ? 'bg-emerald-500' : ($history['change'] < 0 ? 'bg-red-500' : 'bg-themeMuted') }} mt-1">
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-themeHeading">
                                                Stock Level: <span
                                                    class="font-semibold">{{ number_format($history['stock_level']) }}</span>
                                            </div>
                                            @if ($history['movement'])
                                                <div class="text-xs font-medium text-themeMuted mt-1">
                                                    {{ ucfirst(str_replace('_', ' ', $history['movement']->movement_type)) }}
                                                    @if ($history['movement']->creator)
                                                        by {{ $history['movement']->creator->name }}
                                                    @endif
                                                </div>
                                                @if ($history['movement']->notes)
                                                    <div class="text-xs font-medium text-themeMuted mt-1 italic">
                                                        {{ $history['movement']->notes }}</div>
                                                @endif
                                            @else
                                                <div class="text-xs font-medium text-themeMuted mt-1">Current Stock</div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <div
                                                class="text-sm font-medium {{ $history['change'] > 0 ? 'text-emerald-600' : ($history['change'] < 0 ? 'text-red-600' : 'text-themeBody') }}">
                                                @if ($history['change'] > 0)
                                                    +{{ number_format($history['change']) }}
                                                @elseif($history['change'] < 0)
                                                    {{ number_format($history['change']) }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                            <div class="text-xs font-medium text-themeMuted">
                                                {{ $history['date']->format('M d, Y h:i A') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Movements Table -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Stock Movements</h2>
                <span class="text-sm font-medium text-themeMuted">{{ $movements->total() }} total movements</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Stock Before</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Change</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Stock After</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">By
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">
                                        {{ $movement->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs font-medium text-themeMuted">
                                        {{ $movement->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $movement->product->name }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $movement->product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $movement->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $movement->movement_type === 'sale'
                                            ? 'bg-red-100 text-red-800'
                                            : ($movement->movement_type === 'transfer_in'
                                                ? 'bg-green-100 text-green-800'
                                                : ($movement->movement_type === 'transfer_out'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : ($movement->movement_type === 'adjustment'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : ($movement->movement_type === 'stock_take'
                                                            ? 'bg-violet-100 text-violet-800'
                                                            : 'bg-themeHover text-themeHeading')))) }}">
                                        {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ number_format($movement->quantity_before) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-1">
                                        @if ($movement->isIncrease())
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-emerald-600">+{{ number_format($movement->quantity) }}</span>
                                        @else
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-red-600">{{ number_format($movement->quantity) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-themeHeading">
                                        {{ number_format($movement->quantity_after) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $movement->creator->name ?? 'System' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-themeMuted">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-themeMuted mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <span class="font-medium">No movements found for the selected filters</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($movements->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Chart.js CDN -->
    @if ($productId && $branchId && count($stockHistory) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            const primaryColor = '#006F78';
            const successColor = '#10B981';
            const dangerColor = '#EF4444';

            const stockLevelCtx = document.getElementById('stockLevelChart');
            if (stockLevelCtx) {
                const historyData = @json($stockHistory);
                const labels = historyData.map(item => new Date(item.date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }));
                const stockLevels = historyData.map(item => item.stock_level);
                const changes = historyData.map(item => item.change);

                new Chart(stockLevelCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Stock Level',
                            data: stockLevels,
                            borderColor: primaryColor,
                            backgroundColor: primaryColor + '20',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: primaryColor,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            yAxisID: 'y'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const index = context.dataIndex;
                                        const change = changes[index];
                                        if (change !== 0) {
                                            return 'Change: ' + (change > 0 ? '+' : '') + change;
                                        }
                                        return '';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Stock Level'
                                }
                            }
                        }
                    }
                });
            }
        </script>
    @endif
@endsection

