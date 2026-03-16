@extends('layouts.app')

@section('title', 'Inventory Alerts')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Inventory</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Low stock and out-of-stock notifications</p>
            </div>
        </div>
        @include('inventory._subnav', ['current' => 'alerts'])

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Alerts</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Unresolved</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['unresolved'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Low Stock</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['low_stock'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Out of Stock</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['out_of_stock'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('inventory.alerts') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="alert_type" class="block text-sm font-medium text-themeBody mb-2">Alert Type</label>
                    <select id="alert_type" name="alert_type"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Types</option>
                        <option value="low_stock" {{ request('alert_type') == 'low_stock' ? 'selected' : '' }}>Low Stock
                        </option>
                        <option value="out_of_stock" {{ request('alert_type') == 'out_of_stock' ? 'selected' : '' }}>Out of
                            Stock</option>
                        <option value="high_variance" {{ request('alert_type') == 'high_variance' ? 'selected' : '' }}>High
                            Variance</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="resolved" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="resolved" name="resolved"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="0" {{ request('resolved') == '0' ? 'selected' : '' }}>Unresolved</option>
                        <option value="1" {{ request('resolved') == '1' ? 'selected' : '' }}>Resolved</option>
                        <option value="" {{ !request()->has('resolved') ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                    @if (request()->hasAny(['alert_type', 'resolved']))
                        <a href="{{ route('inventory.alerts') }}"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Alert Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Current</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Threshold</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Created</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($alerts as $alert)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $alert->product->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $alert->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $alert->isOutOfStock() ? 'bg-red-100 text-red-800' : ($alert->isLowStock() ? 'bg-amber-100 text-amber-800' : 'bg-violet-100 text-violet-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $alert->current_value }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $alert->threshold_value }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($alert->is_resolved)
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">Resolved</span>
                                        @if ($alert->resolved_at)
                                            <div class="text-xs font-medium text-themeMuted mt-1">
                                                {{ $alert->resolved_at->format('M d, Y') }}</div>
                                        @endif
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Active</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $alert->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if (!$alert->is_resolved && auth()->user()?->hasPermission('inventory.alerts.manage'))
                                        <form method="POST" action="{{ route('inventory.alerts.resolve', $alert) }}"
                                            class="inline" onsubmit="return confirm('Mark this alert as resolved?');">
                                            @csrf
                                            <button type="submit"
                                                class="text-emerald-600 hover:text-emerald-800 font-medium">Resolve</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-themeMuted">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-themeMuted mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                            </path>
                                        </svg>
                                        <span class="font-medium">No alerts found.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                {{ $alerts->links() }}
            </div>
        </div>
    </div>
@endsection

