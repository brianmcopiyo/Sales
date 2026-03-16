@extends('layouts.app')

@section('title', 'Stock Takes')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-operations.index'),
            'label' => 'Back to Stock Operations',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Takes</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Physical inventory counts and variance tracking</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('stock-takes.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export to Excel</span>
                </a>
                @if (auth()->user()?->hasPermission('stock-takes.create'))
                    <a href="{{ route('stock-takes.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Stock Take</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Draft</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['draft'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">In Progress</div>
                <div class="text-2xl font-semibold text-sky-600 tracking-tight">{{ $stats['in_progress'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Completed</div>
                <div class="text-2xl font-semibold text-violet-600 tracking-tight">{{ $stats['completed'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Approved</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['approved'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('stock-takes.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="region_id" class="block text-sm font-medium text-themeBody mb-2">Region</label>
                    <select id="region_id" name="region_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region_id') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                        <select id="branch_id" name="branch_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch['id'] }}" data-region-id="{{ $branch['region_id'] }}"
                                    {{ request('branch_id') == $branch['id'] ? 'selected' : '' }}>
                                    {{ $branch['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress
                        </option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="md:col-span-5 flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if (request()->hasAny(['status', 'date_from', 'date_to', 'branch_id', 'region_id']))
                        <a href="{{ route('stock-takes.index') }}"
                            class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Clear</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($stockTakes as $st)
                    <a href="{{ route('stock-takes.show', $st) }}" class="block px-4 py-4 hover:bg-themeInput/50">
                        <div class="text-sm font-semibold text-primary">{{ $st->stock_take_number ?? '#' }}</div>
                        <div class="text-xs text-themeBody">{{ $st->branch?->name ?? '—' }} · {{ $st->created_at?->format('M d, Y') ?? '—' }}</div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted">No stock takes found.</div>
                @endforelse
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Stock Take #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Items</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Variances</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Created By</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($stockTakes as $stockTake)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('stock-takes.show', $stockTake) }}"
                                        class="text-sm font-medium text-primary hover:text-primary-dark transition">
                                        {{ $stockTake->stock_take_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $stockTake->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $stockTake->stock_take_date->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $stockTake->items->count() }} total
                                        @if ($stockTake->items->whereNotNull('physical_quantity')->count() < $stockTake->items->count())
                                            <span
                                                class="text-amber-600">({{ $stockTake->items->whereNotNull('physical_quantity')->count() }}
                                                counted)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $variances = $stockTake->items->filter(fn($item) => $item->variance !== 0);
                                        $overstock = $variances->filter(fn($item) => $item->variance > 0)->count();
                                        $shortage = $variances->filter(fn($item) => $item->variance < 0)->count();
                                    @endphp
                                    @if ($variances->count() > 0)
                                        <div class="text-sm font-medium">
                                            <span class="text-emerald-600">{{ $overstock }} over</span>
                                            <span class="text-themeMuted">/</span>
                                            <span class="text-red-600">{{ $shortage }} short</span>
                                        </div>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">No variances</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-themeHover text-themeHeading',
                                            'in_progress' => 'bg-sky-100 text-sky-800',
                                            'completed' => 'bg-violet-100 text-violet-800',
                                            'approved' => 'bg-emerald-100 text-emerald-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $statusColors[$stockTake->status] ?? 'bg-themeHover text-themeHeading' }}">
                                        {{ ucfirst(str_replace('_', ' ', $stockTake->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $stockTake->creator->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button @click="open = !open" x-ref="button"
                                            class="text-themeBody hover:text-themeHeading focus:outline-none">
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
                                            class="absolute right-0 top-full z-[9999] mt-2 w-48 bg-themeCard rounded-xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                                            style="display: none;">
                                            <div class="py-1">
                                                @if(auth()->user()?->hasPermission('stock-takes.view'))
                                                <a href="{{ route('stock-takes.show', $stockTake) }}"
                                                    class="block px-4 py-2 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
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
                                                @endif
                                                @if ($stockTake->canBeEdited() && auth()->user()?->hasPermission('stock-takes.update'))
                                                    <a href="{{ route('stock-takes.edit', $stockTake) }}"
                                                        class="block px-4 py-2 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                            </path>
                                                        </svg>
                                                        <span>Edit</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-themeMuted">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-themeMuted mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                            </path>
                                        </svg>
                                        <span class="font-medium">No stock takes found</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stockTakes->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $stockTakes->links() }}
                </div>
            @endif
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const regionSelect = document.getElementById('region_id');
                const branchSelect = document.getElementById('branch_id');

                // Store all branch options with their data
                const allBranchOptions = Array.from(branchSelect.options).map(option => ({
                    value: option.value,
                    text: option.textContent,
                    regionId: option.getAttribute('data-region-id')
                }));

                function filterBranchesByRegion() {
                    const selectedRegionId = regionSelect.value;
                    const currentBranchId = branchSelect.value;

                    // Clear current options
                    branchSelect.innerHTML = '<option value="">All Branches</option>';

                    // Filter and add branches
                    allBranchOptions.forEach(branch => {
                        if (!branch.value) return; // Skip "All Branches"

                        // Show branch if no region selected or if branch belongs to selected region
                        if (!selectedRegionId || branch.regionId === selectedRegionId) {
                            const option = document.createElement('option');
                            option.value = branch.value;
                            option.textContent = branch.text;
                            option.setAttribute('data-region-id', branch.regionId);
                            branchSelect.appendChild(option);
                        }
                    });

                    // Restore selection if it's still valid
                    if (currentBranchId && Array.from(branchSelect.options).some(opt => opt.value ===
                            currentBranchId)) {
                        branchSelect.value = currentBranchId;
                    } else {
                        branchSelect.value = '';
                    }
                }

                // Filter on page load if region is already selected
                filterBranchesByRegion();

                // Filter when region changes
                regionSelect.addEventListener('change', filterBranchesByRegion);
            });
        </script>
    @endif
@endsection

