@extends('layouts.app')

@section('title', 'Stock Operations')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Operations</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Stock takes, adjustments, and transfers</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <a href="{{ route('stock-takes.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Stock Takes</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['stock_takes_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['stock_takes_pending'] }} pending</div>
            </a>
            <a href="{{ route('stock-takes.index', ['status' => 'pending']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Pending Takes</div>
                <div class="text-2xl font-semibold text-amber-600 mt-1">{{ $stats['stock_takes_pending'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">Needs review</div>
            </a>
            <a href="{{ route('stock-adjustments.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Adjustments</div>
                <div class="text-2xl font-semibold text-violet-600 mt-1">{{ $stats['adjustments_total'] }}</div>
            </a>
            <a href="{{ route('stock-transfers.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Transfers</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['transfers_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['transfers_pending'] }} pending</div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'pending']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Pending Transfers</div>
                <div class="text-2xl font-semibold text-amber-600 mt-1">{{ $stats['transfers_pending'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">In progress</div>
            </a>
        </div>

        {{-- Navigation cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (auth()->user()?->hasPermission('stock-takes.view'))
                @include('hubs._hub-card', [
                    'href' => route('stock-takes.index'),
                    'title' => 'Stock Takes',
                    'description' => 'Physical inventory counts and variance tracking',
                    'icon' =>
                        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                ])
            @endif
            @if (auth()->user()?->hasPermission('stock-adjustments.view'))
                @include('hubs._hub-card', [
                    'href' => route('stock-adjustments.index'),
                    'title' => 'Stock Adjustments',
                    'description' => 'Quantity changes from stock takes and manual corrections',
                    'icon' =>
                        'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                ])
            @endif
            @if (auth()->user()?->hasPermission('stock-transfers.view'))
                @include('hubs._hub-card', [
                    'href' => route('stock-transfers.index'),
                    'title' => 'Stock Transfers',
                    'description' => 'Move stock between branches',
                    'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                ])
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @if (auth()->user()?->hasPermission('stock-takes.view') && $recentStockTakes->isNotEmpty())
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Stock Takes</h2>
                        <a href="{{ route('stock-takes.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                    </div>
                    <ul class="divide-y divide-themeBorder" id="stock-takes-list">
                        @foreach ($recentStockTakes as $take)
                            <li class="px-6 py-3 hover:bg-themeInput/50 transition">
                                <a href="{{ route('stock-takes.show', $take) }}"
                                    class="flex justify-between items-center gap-2">
                                    <span
                                        class="text-sm font-medium text-themeHeading truncate">{{ $take->stock_take_number }}</span>
                                    <span
                                        class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium
                                        {{ $take->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($take->status === 'completed' ? 'bg-violet-100 text-violet-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $take->status)) }}
                                    </span>
                                </a>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $take->branch?->name }} ·
                                    {{ $take->stock_take_date?->format('M d, Y') }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (auth()->user()?->hasPermission('stock-transfers.view') && $recentTransfers->isNotEmpty())
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Transfers</h2>
                        <a href="{{ route('stock-transfers.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                    </div>
                    <ul class="divide-y divide-themeBorder" id="transfers-list">
                        @foreach ($recentTransfers as $transfer)
                            <li class="px-6 py-3 hover:bg-themeInput/50 transition">
                                <a href="{{ route('stock-transfers.show', $transfer) }}"
                                    class="flex justify-between items-center gap-2">
                                    <span
                                        class="text-sm font-medium text-themeHeading truncate">{{ $transfer->transfer_number }}</span>
                                    <span
                                        class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium
                                        {{ $transfer->status === 'received' ? 'bg-emerald-100 text-emerald-800' : ($transfer->status === 'pending_sender_confirmation' ? 'bg-orange-100 text-orange-800' : ($transfer->status === 'in_transit' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                                    </span>
                                </a>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $transfer->fromBranch?->name }}
                                    →
                                    {{ $transfer->toBranch?->name }} · {{ $transfer->product?->name }}
                                    ({{ in_array($transfer->status, ['received', 'pending_sender_confirmation']) && $transfer->quantity_received !== null ? $transfer->quantity_received . ' of ' . $transfer->quantity : $transfer->quantity }})
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

@endsection
