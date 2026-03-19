@extends('layouts.app')

@section('title', 'Branches')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('locations.index'),
            'label' => 'Back to Locations',
        ])
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Branches</h1>
            @if (auth()->user()?->hasPermission('branches.create'))
                <a href="{{ route('branches.create') }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add Branch</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Branches</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Active</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $stats['active'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Inactive</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $stats['inactive'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Stock</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ number_format($stats['total_stock']) }}
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('branches.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Branch name..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-48">
                    <label for="region_id" class="block text-sm font-medium text-themeBody mb-2">Region</label>
                    <select id="region_id" name="region_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All regions</option>
                        @foreach ($regions ?? [] as $region)
                            <option value="{{ $region->id }}" {{ request('region_id') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Filter
                </button>
                @if (request()->hasAny(['search', 'region_id', 'status']))
                    <a href="{{ route('branches.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($branches as $branch)
                    @php
                        $branchStock = $stockByBranch->get($branch->id);
                        $totalStock = $branchStock ? (int) $branchStock->total_stock : 0;
                    @endphp
                    <a href="{{ auth()->user()?->hasPermission('branches.view') ? route('branches.show', $branch) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('branches.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary">{{ $branch->name }}</div>
                                <div class="text-xs text-themeBody mt-0.5">{{ $branch->code }} · {{ $branch->region->name ?? '—' }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $branch->headBranch->name ?? '—' }}</div>
                                <div class="text-xs text-themeMuted mt-0.5">{{ number_format($totalStock) }} stock</div>
                                @php
                                    $branchSales = $salesTotalByBranch->get($branch->id);
                                @endphp
                                <div class="text-xs text-themeMuted mt-0.5">Sales: {{ $currencySymbol }} {{ number_format($branchSales ? (float) $branchSales->total_sales : 0, 2) }}</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium flex-shrink-0 {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No branches found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Region</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Head Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Total sales</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($branches as $branch)
                            <tr class="hover:bg-themeInput/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading flex items-center"
                                        style="padding-left: {{ ($branch->hierarchy_level ?? 0) * 24 }}px;">
                                        @if (($branch->hierarchy_level ?? 0) > 0)
                                            <svg class="w-4 h-4 text-themeMuted mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        @endif
                                        <span>{{ $branch->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $branch->code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $branch->region->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $branch->headBranch->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $branchStock = $stockByBranch->get($branch->id);
                                        $totalStock = $branchStock ? (int) $branchStock->total_stock : 0;
                                    @endphp
                                    <div class="text-sm font-medium text-themeHeading">{{ number_format($totalStock) }}
                                    </div>
                                    @if ($totalStock > 0 && auth()->user()?->hasPermission('branch-stocks.view'))
                                        <a href="{{ route('branch-stocks.index') }}?branch_id={{ $branch->id }}"
                                            class="text-xs text-primary hover:text-primary-dark font-medium">View stock</a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php $branchSales = $salesTotalByBranch->get($branch->id); @endphp
                                    <div class="text-sm font-medium text-themeBody">{{ $currencySymbol }} {{ number_format($branchSales ? (float) $branchSales->total_sales : 0, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
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
                                            <div class="py-1.5">
                                                @if (auth()->user()?->hasPermission('branches.view'))
                                                    <a href="{{ route('branches.show', $branch) }}"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput hover:text-primary transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                                            </path>
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                            </path>
                                                        </svg>
                                                        <span>View</span>
                                                    </a>
                                                @endif
                                                @if (auth()->user()?->hasPermission('branches.update'))
                                                    <a href="{{ route('branches.edit', $branch) }}"
                                                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput hover:text-primary transition">
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
                                                @if (auth()->user()?->hasPermission('branches.delete'))
                                                    <form action="{{ route('branches.delete', $branch) }}" method="POST"
                                                        onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-themeInput transition text-left">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                            <span>Delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-themeMuted font-medium">No branches
                                    found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($branches->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $branches->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
