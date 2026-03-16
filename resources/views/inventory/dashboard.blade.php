@extends('layouts.app')

@section('title', 'Inventory Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Inventory</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Real-time inventory insights and analytics</p>
            </div>
        </div>

        @include('inventory._subnav', ['current' => 'dashboard'])

        <!-- Enhanced Key Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Items Card -->
            <a href="{{ route('inventory.dashboard') }}" 
                class="filter-card bg-gradient-to-br from-[#006F78] to-[#005a62] rounded-2xl border border-[#006F78]/20 p-6 text-white shadow-[0_2px_15px_-3px_rgba(0,111,120,0.25),0_10px_20px_-2px_rgba(0,0,0,0.15)] cursor-pointer transition-all hover:shadow-xl hover:scale-[1.02]"
                data-filter="movements-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-themeCard bg-opacity-20 rounded-lg p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    @if ($stats['movement_trend'] != 0)
                        <span
                            class="text-xs bg-themeCard bg-opacity-20 px-2 py-1 rounded {{ $stats['movement_trend'] > 0 ? 'text-green-200' : 'text-red-200' }}">
                            {{ $stats['movement_trend'] > 0 ? '+' : '' }}{{ $stats['movement_trend'] }}%
                        </span>
                    @endif
                </div>
                <div class="text-sm font-medium mb-1 opacity-90">Total Items</div>
                <div class="text-3xl font-semibold tracking-tight mb-1">{{ number_format($stats['total_items']) }}</div>
                <div class="text-xs font-medium opacity-75">{{ number_format($stats['total_quantity']) }} units</div>
            </a>

            <!-- Low Stock Card -->
            <a href="{{ route('products.index', ['status' => 'active', 'low_stock' => '1']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="low-stock">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-amber-100 rounded-xl p-3">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    @if ($stats['low_stock_count'] > 0)
                        <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-amber-100 text-amber-800">Action
                            Required</span>
                    @endif
                </div>
                <div class="text-sm font-medium text-themeMuted mb-1">Low Stock</div>
                <div class="text-3xl font-semibold text-amber-600 tracking-tight mb-1">{{ $stats['low_stock_count'] }}</div>
                <div class="text-xs font-medium text-themeMuted">Items below minimum level</div>
            </a>

            <!-- Out of Stock Card -->
            <a href="{{ route('products.index', ['status' => 'active', 'out_of_stock' => '1']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="out-of-stock">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-red-100 rounded-xl p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </div>
                    @if ($stats['out_of_stock_count'] > 0)
                        <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-red-100 text-red-800">Critical</span>
                    @endif
                </div>
                <div class="text-sm font-medium text-themeMuted mb-1">Out of Stock</div>
                <div class="text-3xl font-semibold text-red-600 tracking-tight mb-1">{{ $stats['out_of_stock_count'] }}
                </div>
                <div class="text-xs font-medium text-themeMuted">Items with zero stock</div>
            </a>

            <!-- Active Alerts Card -->
            <a href="{{ route('inventory.alerts') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="active-alerts">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-violet-100 rounded-xl p-3">
                        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                    </div>
                    @if ($stats['active_alerts'] > 0)
                        <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-violet-100 text-violet-800">New</span>
                    @endif
                </div>
                <div class="text-sm font-medium text-themeMuted mb-1">Active Alerts</div>
                <div class="text-3xl font-semibold text-violet-600 tracking-tight mb-1">{{ $stats['active_alerts'] }}</div>
                <div class="text-xs font-medium text-themeMuted">Unresolved alerts</div>
            </a>
        </div>

        <!-- Additional Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('inventory.movements', ['date_from' => today()->format('Y-m-d'), 'date_to' => today()->format('Y-m-d')]) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="movements-today">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-themeMuted mb-1">Today's Movements</div>
                        <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $stats['today_movements'] }}
                        </div>
                    </div>
                    <div class="bg-sky-100 rounded-xl p-3">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </a>
            <a href="{{ route('stock-takes.index', ['status' => 'pending']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="pending-stock-takes">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-themeMuted mb-1">Pending Stock Takes</div>
                        <div class="text-2xl font-semibold text-sky-600 tracking-tight">{{ $stats['pending_stock_takes'] }}
                        </div>
                    </div>
                    <div class="bg-sky-100 rounded-xl p-3">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                </div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'pending']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30"
                data-filter="pending-transfers">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-themeMuted mb-1">Pending Transfers</div>
                        <div class="text-2xl font-semibold text-indigo-600 tracking-tight">
                            {{ $stats['pending_transfers'] }}</div>
                    </div>
                    <div class="bg-indigo-100 rounded-xl p-3">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Movement Trends Chart -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Movement Trends (Last 7 Days)</h2>
                </div>
                <div class="h-64">
                    <canvas id="movementTrendsChart"></canvas>
                </div>
            </div>

            <!-- Movement Types Distribution -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Movement Types (Last 30 Days)</h2>
                </div>
                <div class="h-64">
                    <canvas id="movementTypesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Low Stock Items -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Low Stock Items</h2>
                    <a href="{{ route('inventory.alerts') }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark transition flex items-center gap-1">
                        <span>View All</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
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
                                    Stock</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Min Level</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder" id="low-stock-table-body">
                            @forelse($lowStockItems->take(5) as $stock)
                                <tr class="filterable-row hover:bg-orange-50 transition-colors"
                                    data-stock-type="{{ $stock->display_quantity == 0 ? 'out-of-stock' : 'low-stock' }}"
                                    data-filter-group="low-stock-items">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $stock->product->name }}
                                        </div>
                                        <div class="text-xs font-medium text-themeMuted">{{ $stock->product->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">{{ $stock->branch->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">{{ $stock->display_quantity }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">
                                            {{ $stock->product->minimum_stock_level ?? 10 }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-themeMuted">
                                        <div class="flex flex-col items-center py-4">
                                            <svg class="w-12 h-12 text-themeMuted mb-2" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="font-medium">No low stock items</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Movements -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Movements</h2>
                    <a href="{{ route('inventory.movements') }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark transition flex items-center gap-1">
                        <span>View All</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
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
                                    Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder" id="movements-table-body">
                            @forelse($recentMovements as $movement)
                                <tr class="hover:bg-themeInput transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $movement->product->name }}
                                        </div>
                                        <div class="text-xs font-medium text-themeMuted">{{ $movement->product->sku }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $movement->movement_type === 'sale'
                                                ? 'bg-red-100 text-red-800'
                                                : ($movement->movement_type === 'transfer_in'
                                                    ? 'bg-green-100 text-green-800'
                                                    : ($movement->movement_type === 'adjustment'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : 'bg-themeHover text-themeHeading')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-1">
                                            @if ($movement->isIncrease())
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                                </svg>
                                            @endif
                                            <span
                                                class="text-sm font-medium {{ $movement->isIncrease() ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $movement->formatted_quantity }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">
                                            {{ $movement->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs font-medium text-themeMuted">
                                            {{ $movement->created_at->format('h:i A') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-themeMuted">
                                        <div class="flex flex-col items-center py-4">
                                            <svg class="w-12 h-12 text-themeMuted mb-2" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <span class="font-medium">No recent movements</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Products & Pending Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products by Movement -->
            @if ($topProductsByMovement->count() > 0)
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Most Active Products (Last 30 Days)
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach ($topProductsByMovement as $index => $productMovement)
                                <div
                                    class="flex items-center justify-between p-3 rounded-xl bg-themeInput/80 hover:bg-themeInput transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-xl flex items-center justify-center font-medium text-sm">
                                            {{ $index + 1 }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-themeHeading">
                                                {{ $productMovement->product->name }}</div>
                                            <div class="text-xs font-medium text-themeMuted">
                                                {{ $productMovement->movement_count }} movements</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-primary">
                                            {{ number_format($productMovement->total_quantity) }}</div>
                                        <div class="text-xs font-medium text-themeMuted">units</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Pending Actions -->
            <div class="space-y-6">
                <!-- Pending Stock Takes -->
                @if ($pendingStockTakes->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <div
                            class="px-6 py-4 border-b border-themeBorder flex justify-between items-center bg-themeInput/80">
                            <h2 class="text-lg font-semibold text-primary tracking-tight">Pending Stock Takes</h2>
                            <a href="{{ route('stock-takes.index') }}"
                                class="text-sm font-medium text-primary hover:text-primary-dark transition">View All</a>
                        </div>
                        <div class="p-6">
                            <ul class="space-y-3" id="pending-stock-takes-list">
                                @foreach ($pendingStockTakes as $stockTake)
                                    <li class="filterable-row flex justify-between items-center p-3 rounded-xl bg-sky-50/80 hover:bg-sky-50 transition-colors"
                                        data-filter-group="pending-stock-takes">
                                        <div>
                                            <a href="{{ route('stock-takes.show', $stockTake) }}"
                                                class="text-sm font-medium text-primary hover:text-primary-dark transition">
                                                {{ $stockTake->stock_take_number }}
                                            </a>
                                            <div class="text-xs font-medium text-themeMuted">
                                                {{ $stockTake->branch->name }}
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-sky-100 text-sky-800">
                                            {{ ucfirst(str_replace('_', ' ', $stockTake->status)) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Active Alerts -->
                @if ($activeAlerts->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <div
                            class="px-6 py-4 border-b border-themeBorder flex justify-between items-center bg-themeInput/80">
                            <h2 class="text-lg font-semibold text-primary tracking-tight">Active Alerts</h2>
                            <a href="{{ route('inventory.alerts') }}"
                                class="text-sm font-medium text-primary hover:text-primary-dark transition">View All</a>
                        </div>
                        <div class="p-6">
                            <ul class="space-y-3" id="active-alerts-list">
                                @foreach ($activeAlerts->take(5) as $alert)
                                    <li class="flex justify-between items-center p-3 rounded-xl {{ $alert->isOutOfStock() ? 'bg-red-50/80 hover:bg-red-50' : 'bg-amber-50/80 hover:bg-amber-50' }} transition-colors">
                                        <div>
                                            <div class="text-sm font-medium text-themeHeading">{{ $alert->product->name }}
                                            </div>
                                            <div class="text-xs font-medium text-themeMuted">{{ $alert->branch->name }}
                                            </div>
                                        </div>
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $alert->isOutOfStock() ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
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

        // Movement Trends Chart (Line Chart)
        const movementTrendsCtx = document.getElementById('movementTrendsChart');
        if (movementTrendsCtx) {
            new Chart(movementTrendsCtx, {
                type: 'line',
                data: {
                    labels: @json($movementChartLabels),
                    datasets: [{
                        label: 'Movements',
                        data: @json($movementChartData),
                        borderColor: primaryColor,
                        backgroundColor: primaryColor + '20',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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

        // Movement Types Distribution (Doughnut Chart)
        const movementTypesCtx = document.getElementById('movementTypesChart');
        if (movementTypesCtx) {
            const movementTypeColors = {
                'Sale': dangerColor,
                'Transfer In': successColor,
                'Transfer Out': warningColor,
                'Adjustment': infoColor,
                'Stock Take': secondaryColor
            };

            new Chart(movementTypesCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($movementTypeLabels),
                    datasets: [{
                        data: @json($movementTypeData),
                        backgroundColor: @json($movementTypeLabels).map(label => movementTypeColors[
                            label] || primaryColor + '80'),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                        }
                    }
                }
            });
        }

    </script>
@endsection
