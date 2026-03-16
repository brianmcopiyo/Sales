@extends('layouts.app')

@section('title', 'Restock Orders')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-management.index'),
            'label' => 'Back to Stock Management',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Restock Orders</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">All restock orders and receipts</p>
            </div>
            <a href="{{ route('stock-management.restock-orders.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Export to Excel</span>
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Pending</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['pending'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Partial</div>
                <div class="text-2xl font-semibold text-sky-600 tracking-tight">{{ $stats['received_partial'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Received</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['received_full'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Rejected</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['cancelled'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('stock-management.restock-orders.index') }}"
                class="flex flex-wrap gap-4 items-end">
                <div class="w-44">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="received_partial" {{ request('status') === 'received_partial' ? 'selected' : '' }}>
                            Partial</option>
                        <option value="received_full" {{ request('status') === 'received_full' ? 'selected' : '' }}>Received
                        </option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Rejected
                        </option>
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
                @if (request()->hasAny(['status', 'date_from', 'date_to']))
                    <a href="{{ route('stock-management.restock-orders.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Order</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Ordered</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Received</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Total acquisition cost</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Ordered at</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Received at</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($orders as $order)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('stock-management.orders.show', $order) }}"
                                        class="text-sm font-medium text-primary hover:underline">{{ $order->display_order_number }}</a>
                                    @if ($order->order_batch)
                                        <span class="text-xs text-themeMuted">(line)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $order->product->name }}</div>
                                    <div class="text-xs text-themeMuted">{{ $order->product->sku ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">
                                    {{ $order->branch->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">
                                    {{ $order->quantity_ordered }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">
                                    {{ $order->quantity_received }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeMuted">
                                    {{ $order->reference_number ?? '–' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">
                                    {{ $order->total_acquisition_cost !== null ? number_format($order->total_acquisition_cost, 2) : '–' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($order->status === 'received_full')
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">Received</span>
                                    @elseif ($order->status === 'received_partial')
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-sky-100 text-sky-800">Partial</span>
                                    @elseif ($order->status === 'cancelled')
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-red-100 text-red-800">Rejected</span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeMuted">
                                    {{ $order->ordered_at ? $order->ordered_at->format('M d, Y') : '–' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeMuted">
                                    {{ $order->received_at ? $order->received_at->format('M d, Y H:i') : '–' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button type="button" @click="open = !open"
                                            class="text-themeBody hover:text-themeHeading focus:outline-none p-1 rounded-lg hover:bg-themeHover">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                                </path>
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute right-0 top-full z-[9999] mt-2 w-48 bg-themeCard rounded-xl border border-themeBorder shadow-lg"
                                            style="display: none;">
                                            <div class="py-1.5">
                                                <a href="{{ route('stock-management.orders.show', $order) }}"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                        </path>
                                                    </svg>
                                                    <span>View</span>
                                                </a>
                                                <a href="{{ route('stock-management.orders.show', $order) }}#transfer"
                                                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                    </svg>
                                                    <span>Transfer to branch</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center text-themeMuted font-medium">No restock
                                    orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder">
                    {{ $orders->links('vendor.pagination.simple-tailwind') }}
                </div>
            @endif
        </div>
    </div>
@endsection
