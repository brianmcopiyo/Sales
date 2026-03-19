@extends('layouts.app')

@section('title', 'Sales & Transactions')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Sales & Transactions</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Sales orders and payment transactions</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('sales.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Total Sales</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['sales_total'] }}</div>
            </a>
            <a href="{{ route('sales.index', ['status' => 'completed']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Completed</div>
                <div class="text-2xl font-semibold text-emerald-600 mt-1">{{ $stats['sales_completed'] }}</div>
            </a>
            <a href="{{ route('sales.index', ['date_from' => today()->format('Y-m-d'), 'date_to' => today()->format('Y-m-d')]) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Today</div>
                <div class="text-2xl font-semibold text-amber-600 mt-1">{{ $stats['sales_today'] }}</div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if (auth()->user()?->hasPermission('sales.view'))
                @include('hubs._hub-card', [
                    'href' => route('sales.index'),
                    'title' => 'Sales',
                    'description' => 'Sales orders and history',
                    'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                ])
                @include('hubs._hub-card', [
                    'href' => route('sales-stats.index'),
                    'title' => 'Sales Stats',
                    'description' => 'Best performing users and products by sales',
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                ])
            @endif
            @if (auth()->user()?->hasPermission('transactions.view'))
                @include('hubs._hub-card', [
                    'href' => route('transactions.index'),
                    'title' => 'Transactions',
                    'description' => 'Payment and transaction history',
                    'icon' =>
                        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ])
            @endif
            @if (auth()->user()?->hasPermission('sales.view') || auth()->user()?->hasPermission('stock-requests.view'))
                @include('hubs._hub-card', [
                    'href' => route('device-requests.index'),
                    'title' => 'Device Requests',
                    'description' => 'Request devices from other branches, approve or reject incoming requests',
                    'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                ])
            @endif
        </div>

        @if (auth()->user()?->hasPermission('sales.view') && $recentSales->isNotEmpty())
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Most recent orders</h2>
                    <a href="{{ route('sales.index') }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-themeBorder" id="sales-list">
                            @foreach ($recentSales as $sale)
                                <tr class="hover:bg-themeInput/50 transition">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-primary hover:underline">{{ $sale->sale_number }}</a>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-themeBody">{{ $sale->customer?->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-sm font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($sale->total ?? 0, 2) }}</td>
                                    <td class="px-6 py-3 text-sm font-medium text-themeBody">{{ $currencySymbol }} {{ number_format($sale->total_cost_to_sell, 2) }}</td>
                                    <td class="px-6 py-3 text-sm text-themeMuted">{{ $sale->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($sale->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

@endsection
