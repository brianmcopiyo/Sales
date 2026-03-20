@extends('layouts.portal')

@section('title', 'My Orders')

@section('content')
<div class="py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-xl font-bold" style="color:#111827;">My Orders</h1>
        <a href="{{ route('portal.orders.export', request()->query()) }}"
           class="text-sm px-4 py-2 rounded-lg font-medium text-white transition-opacity hover:opacity-90"
           style="background-color:#006F78;">
            Export Excel
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl p-4 border shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Orders</p>
            <p class="text-2xl font-bold mt-1" style="color:#111827;">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl p-4 border shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Completed</p>
            <p class="text-2xl font-bold mt-1 text-green-600">{{ $stats['completed'] }}</p>
        </div>
        <div class="rounded-xl p-4 border shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</p>
            <p class="text-2xl font-bold mt-1" style="color:#111827;">{{ number_format($stats['revenue'], 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order #..."
               class="text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20"
               style="border-color:#e5e7eb;">
        <select name="status" class="text-sm border rounded-lg px-3 py-2 focus:outline-none"
                style="border-color:#e5e7eb;">
            <option value="">All Statuses</option>
            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="text-sm border rounded-lg px-3 py-2 focus:outline-none" style="border-color:#e5e7eb;">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="text-sm border rounded-lg px-3 py-2 focus:outline-none" style="border-color:#e5e7eb;">
        <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">
            Filter
        </button>
        @if (request()->hasAny(['search', 'status', 'date_from', 'date_to']))
            <a href="{{ route('portal.orders.index') }}" class="text-sm px-4 py-2 rounded-lg border font-medium"
               style="border-color:#e5e7eb; color:#374151;">Clear</a>
        @endif
    </form>

    {{-- Orders Table --}}
    <div class="rounded-xl border shadow-sm overflow-hidden" style="background:#fff; border-color:#e5e7eb;">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Order #</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Products</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Outlet</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Total</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t hover:bg-gray-50" style="border-color:#f3f4f6;">
                            <td class="px-4 py-3">
                                <a href="{{ route('portal.orders.show', $order) }}" class="font-medium hover:underline" style="color:#006F78;">
                                    {{ $order->sale_number ?? $order->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#374151;">
                                {{ $order->items->map(fn($i) => $i->product?->name ?? 'N/A')->take(2)->join(', ') }}
                                @if ($order->items->count() > 2)
                                    <span style="color:#6b7280;">+{{ $order->items->count() - 2 }} more</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $order->outlet?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium" style="color:#111827;">{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $order->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm" style="color:#6b7280;">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="px-4 py-3 border-t" style="border-color:#e5e7eb;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
