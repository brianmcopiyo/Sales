@extends('layouts.portal')

@section('title', 'Dashboard')

@section('content')
<div class="py-6">
    {{-- Welcome Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color: var(--tw-color-themeBody, #1f2937);">
            Welcome, {{ $profile->customer->name }}
        </h1>
        <p class="text-sm mt-1" style="color: #6b7280;">{{ now()->format('l, F j Y') }}</p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl p-4 shadow-sm border" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Revenue MTD</p>
            <p class="text-2xl font-bold mt-1" style="color:#111827;">{{ number_format($revenueMtd, 2) }}</p>
        </div>
        <div class="rounded-xl p-4 shadow-sm border" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Revenue YTD</p>
            <p class="text-2xl font-bold mt-1" style="color:#111827;">{{ number_format($revenueYtd, 2) }}</p>
        </div>
        <div class="rounded-xl p-4 shadow-sm border" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Orders This Month</p>
            <p class="text-2xl font-bold mt-1" style="color:#111827;">{{ $ordersMtd }}</p>
        </div>
        <div class="rounded-xl p-4 shadow-sm border" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Outstanding Balance</p>
            <p class="text-2xl font-bold mt-1" style="color:#111827;">{{ number_format($profile->outstanding_balance, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Revenue Chart --}}
        <div class="lg:col-span-2 rounded-xl border p-5 shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <h2 class="text-sm font-semibold mb-4" style="color:#111827;">Revenue — Last 6 Months</h2>
            @php
                $maxRevenue = collect($monthlyRevenue)->max('revenue') ?: 1;
            @endphp
            <div class="flex items-end gap-2 h-40">
                @foreach ($monthlyRevenue as $m)
                    @php $pct = ($m['revenue'] / $maxRevenue) * 100; @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-xs" style="color:#6b7280;">{{ number_format($m['revenue'] / 1000, 1) }}k</span>
                        <div class="w-full rounded-t-md" style="height:{{ max(4, $pct * 0.9) }}%; background:#006F78; min-height:4px;"></div>
                        <span class="text-xs whitespace-nowrap" style="color:#6b7280;">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Active Schemes --}}
        <div class="rounded-xl border p-5 shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold" style="color:#111827;">Active Schemes</h2>
                <a href="{{ route('portal.schemes.index') }}" class="text-xs font-medium" style="color:#006F78;">View all</a>
            </div>
            @forelse ($activeSchemes->take(5) as $scheme)
                <div class="mb-3 pb-3 border-b last:border-0 last:mb-0 last:pb-0" style="border-color:#f3f4f6;">
                    <p class="text-sm font-medium" style="color:#111827;">{{ $scheme->name }}</p>
                    <p class="text-xs mt-0.5" style="color:#6b7280;">
                        Ends {{ \Carbon\Carbon::parse($scheme->end_date)->diffForHumans() }}
                    </p>
                </div>
            @empty
                <p class="text-sm" style="color:#6b7280;">No active schemes at the moment.</p>
            @endforelse

            @if ($pendingClaims > 0)
                <div class="mt-4 p-3 rounded-lg" style="background:#fef3c7; border:1px solid #fcd34d;">
                    <p class="text-xs font-medium" style="color:#92400e;">
                        {{ $pendingClaims }} claim{{ $pendingClaims > 1 ? 's' : '' }} pending review.
                        <a href="{{ route('portal.claims.index') }}" class="underline">View claims</a>
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="mt-6 rounded-xl border shadow-sm overflow-hidden" style="background:#fff; border-color:#e5e7eb;">
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:#e5e7eb;">
            <h2 class="text-sm font-semibold" style="color:#111827;">Recent Orders</h2>
            <a href="{{ route('portal.orders.index') }}" class="text-xs font-medium" style="color:#006F78;">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Order #</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Items</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Total</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr class="border-t hover:bg-gray-50 transition-colors" style="border-color:#f3f4f6;">
                            <td class="px-4 py-3">
                                <a href="{{ route('portal.orders.show', $order) }}" class="font-medium hover:underline" style="color:#006F78;">
                                    {{ $order->sale_number ?? $order->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3" style="color:#374151;">{{ $order->items->count() }}</td>
                            <td class="px-4 py-3 font-medium" style="color:#111827;">{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $order->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm" style="color:#6b7280;">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
