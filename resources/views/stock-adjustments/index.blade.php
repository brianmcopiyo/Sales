@extends('layouts.app')

@section('title', 'Stock Adjustments')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-operations.index'),
            'label' => 'Back to Stock Operations',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Adjustments</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Quantity changes from stock takes and manual corrections
                </p>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Adjustments</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Increases</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['increases'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-1">+{{ number_format($stats['total_increase_amount']) }}
                </div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Decreases</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['decreases'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-1">-{{ number_format($stats['total_decrease_amount']) }}
                </div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">From Stock Takes</div>
                <div class="text-2xl font-semibold text-violet-600 tracking-tight">{{ $stats['from_stock_takes'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">From Sales</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['from_sales'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('stock-adjustments.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                    <select id="product_id" name="product_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="adjustment_type" class="block text-sm font-medium text-themeBody mb-2">Type</label>
                    <select id="adjustment_type" name="adjustment_type"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Types</option>
                        <option value="stock_take" {{ request('adjustment_type') == 'stock_take' ? 'selected' : '' }}>Stock
                            Take</option>
                        <option value="manual" {{ request('adjustment_type') == 'manual' ? 'selected' : '' }}>Manual
                        </option>
                        <option value="correction" {{ request('adjustment_type') == 'correction' ? 'selected' : '' }}>
                            Correction</option>
                        <option value="reconciliation" {{ request('adjustment_type') == 'reconciliation' ? 'selected' : '' }}>Reconciliation</option>
                        <option value="sale" {{ request('adjustment_type') == 'sale' ? 'selected' : '' }}>Sale</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                    @if (request()->hasAny(['branch_id', 'product_id', 'adjustment_type', 'date_from', 'date_to']))
                        <a href="{{ route('stock-adjustments.index') }}"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($adjustments as $adj)
                    <a href="{{ route('stock-adjustments.show', $adj) }}" class="block px-4 py-4 hover:bg-themeInput/50 transition-colors">
                        <div class="text-sm font-semibold text-primary">{{ $adj->adjustment_number ?? '#' }}</div>
                        <div class="text-xs text-themeBody mt-0.5">{{ $adj->branch?->name ?? '—' }}</div>
                        <div class="text-xs text-themeMuted mt-1">{{ $adj->created_at?->format('M d, Y') ?? '—' }}</div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No adjustments found.</div>
                @endforelse
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Adjustment #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Before</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                After</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Adjustment</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($adjustments as $adjustment)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-primary">{{ $adjustment->adjustment_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $adjustment->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $adjustment->product->name }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $adjustment->product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $adjustment->quantity_before }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $adjustment->quantity_after }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($adjustment->adjustment_amount > 0)
                                        <span
                                            class="text-sm font-medium text-emerald-600">+{{ $adjustment->adjustment_amount }}</span>
                                    @elseif($adjustment->adjustment_amount < 0)
                                        <span
                                            class="text-sm font-medium text-red-600">{{ $adjustment->adjustment_amount }}</span>
                                    @else
                                        <span class="text-sm font-medium text-themeBody">0</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">
                                        {{ ucfirst(str_replace('_', ' ', $adjustment->adjustment_type)) }}
                                    </span>
                                    @if ($adjustment->stock_take_id)
                                        <div class="text-xs font-medium text-themeMuted mt-1">
                                            <a href="{{ route('stock-takes.show', $adjustment->stockTake) }}"
                                                class="font-medium text-primary hover:text-primary-dark transition">
                                                {{ $adjustment->stockTake->stock_take_number }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $adjustment->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs font-medium text-themeMuted">
                                        {{ $adjustment->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('stock-adjustments.show', $adjustment) }}"
                                        class="text-primary hover:text-primary-dark transition">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-themeMuted font-medium">No adjustments
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                {{ $adjustments->links() }}
            </div>
        </div>
    </div>
@endsection

