@extends('layouts.app')

@section('title', 'Overstayed Devices')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('devices.index'), 'label' => 'Back to Devices'])

        <!-- Filters -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('devices.overstayed') }}" class="flex flex-wrap gap-4 items-end">
                @php $currentBranch = request('branch_id') ?? request('branch'); @endphp
                <div class="w-36">
                    <label for="days" class="block text-sm font-medium text-themeBody mb-2">Min. days</label>
                    <select id="days" name="days" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="5" {{ (int)($days ?? 5) === 5 ? 'selected' : '' }}>5</option>
                        <option value="15" {{ (int)($days ?? 5) === 15 ? 'selected' : '' }}>15</option>
                        <option value="30" {{ (int)($days ?? 5) === 30 ? 'selected' : '' }}>30</option>
                        <option value="60" {{ (int)($days ?? 5) === 60 ? 'selected' : '' }}>60</option>
                        <option value="90" {{ (int)($days ?? 5) === 90 ? 'selected' : '' }}>90</option>
                        <option value="180" {{ (int)($days ?? 5) === 180 ? 'selected' : '' }}>180</option>
                    </select>
                </div>
                <div class="w-48">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All branches</option>
                        @foreach ($branches ?? [] as $b)
                            <option value="{{ $b->id }}" {{ $currentBranch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-56">
                    <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                    <select id="product_id" name="product_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All products</option>
                        @foreach ($products ?? [] as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">IMEI</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Search by IMEI..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request()->hasAny(['search', 'product_id', 'branch_id', 'branch', 'days']))
                    <a href="{{ route('devices.overstayed') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
                <a href="{{ route('devices.overstayed.export', request()->query()) }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </a>
            </form>
        </div>

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Overstayed Devices</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Devices in stock longer than <strong>{{ $days }}</strong> days (by IMEI)</p>
            </div>
        </div>

        <!-- Analytics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total overstayed</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </div>
            @if ($days < 30)
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="text-xs font-medium text-themeMuted mb-1">{{ $days }}–30 days</div>
                    <div class="text-2xl font-semibold text-amber-600">{{ $stats['age_first'] ?? 0 }}</div>
                </div>
            @endif
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">30–60 days</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['age_30_60'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">60–90 days</div>
                <div class="text-2xl font-semibold text-orange-600">{{ $stats['age_60_90'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">90+ days</div>
                <div class="text-2xl font-semibold text-red-600">{{ $stats['age_90_plus'] }}</div>
            </div>
        </div>

        <!-- By branch / by product -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">By branch</h2>
                @if ($stats['by_branch']->isEmpty())
                    <p class="text-sm text-themeMuted">No data</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($stats['by_branch'] as $row)
                            <li class="flex justify-between items-center text-sm">
                                <span class="font-medium text-themeBody">{{ $row->branch?->name ?? 'Unassigned' }}</span>
                                <span class="font-semibold text-primary">{{ $row->count }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">By product</h2>
                @if ($stats['by_product']->isEmpty())
                    <p class="text-sm text-themeMuted">No data</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($stats['by_product'] as $row)
                            <li class="flex justify-between items-center text-sm">
                                <span class="font-medium text-themeBody">{{ $row->product?->name ?? '—' }}</span>
                                <span class="font-semibold text-primary">{{ $row->count }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($devices as $device)
                    <a href="{{ auth()->user()?->hasPermission('devices.view') ? route('devices.show', $device) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('devices.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary">{{ $device->imei }}</div>
                                <div class="text-sm text-themeBody mt-0.5">{{ $device->product->name ?? '—' }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $device->branch ? $device->branch->name : '—' }}</div>
                                <div class="text-xs text-themeMuted mt-0.5">{{ $device->created_at?->format('M j, Y') ?? '—' }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-0.5 flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $device->status === 'assigned' ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody' }}">{{ ucfirst($device->status) }}</span>
                                <span class="text-xs font-semibold text-amber-600">{{ (int)($device->days_in_stock ?? 0) }} days</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No overstayed devices found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">IMEI</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Days stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date added</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($devices as $device)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if (auth()->user()?->hasPermission('devices.view'))
                                        <a href="{{ route('devices.show', $device) }}" class="text-sm font-medium text-primary hover:underline">{{ $device->imei }}</a>
                                    @else
                                        <span class="text-sm font-medium text-themeHeading">{{ $device->imei }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $device->product->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $device->branch ? $device->branch->name : '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $device->status === 'assigned' ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody' }}">{{ ucfirst($device->status) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-amber-600">{{ (int)($device->days_in_stock ?? 0) }} days</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">{{ $device->created_at?->format('M j, Y') ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if (auth()->user()?->hasPermission('devices.view'))
                                        <a href="{{ route('devices.show', $device) }}" class="text-primary hover:underline">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No overstayed devices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($devices->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $devices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
