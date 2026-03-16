@extends('layouts.app')

@section('title', 'Devices')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('catalog.index'), 'label' => 'Back to Catalog'])

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Devices</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">IMEI-tracked devices by product</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('devices.view'))
                    <a href="{{ route('devices.overstayed') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Overstayed</span>
                    </a>
                    <a href="{{ route('devices.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('devices.view'))
                    <button type="button"
                        onclick="document.getElementById('reconcile-imei-modal').classList.remove('hidden')"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span>Reconcile IMEIs</span>
                    </button>
                @endif
                @if (auth()->user()?->hasPermission('devices.create'))
                    <button type="button"
                        onclick="document.getElementById('import-devices-modal').classList.remove('hidden')"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        <span>Import</span>
                    </button>
                    <a href="{{ route('devices.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Device</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Available</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['available'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Sold</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['sold'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Assigned</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['assigned'] }}</div>
            </div>
        </div>

        @if (session('import_report'))
            @php $report = session('import_report'); @endphp
            <div class="rounded-2xl border border-themeBorder bg-themeCard p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h3 class="text-base font-semibold text-themeHeading mb-3">Import report</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-2">Already existed (IMEI → branch)</p>
                        @if (count($report['already_existed'] ?? []) > 0)
                            <div class="overflow-x-auto max-h-52 overflow-y-auto rounded-xl border border-themeBorder">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-themeHover">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-themeBody">IMEI</th>
                                            <th class="px-3 py-2 text-left font-medium text-themeBody">Branch</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-themeBorder">
                                        @foreach ($report['already_existed'] as $item)
                                            <tr>
                                                <td class="px-3 py-1.5 text-themeBody">{{ $item['imei'] }}</td>
                                                <td class="px-3 py-1.5 text-themeBody">{{ $item['branch'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-themeMuted">None</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-themeMuted uppercase tracking-wider mb-2">Added to {{ $report['branch_name'] ?? 'selected branch' }}</p>
                        @if (count($report['added'] ?? []) > 0)
                            <ul class="list-disc list-inside text-sm text-themeBody space-y-0.5 max-h-52 overflow-y-auto">
                                @foreach ($report['added'] as $imei)
                                    <li>{{ $imei }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-themeMuted">None</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('devices.index') }}" class="flex flex-wrap gap-4 items-end">
                @php $currentBranch = request('branch_id') ?? request('branch'); @endphp
                <div class="w-48">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All branches</option>
                        @foreach ($branches ?? [] as $b)
                            <option value="{{ $b->id }}" {{ $currentBranch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Search by IMEI..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-48">
                    <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                    <select id="product_id" name="product_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All products</option>
                        @foreach ($products ?? [] as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available
                        </option>
                        <option value="sold" {{ request('status') === 'sold' ? 'selected' : '' }}>Sold</option>
                    </select>
                </div>
                <div class="w-40">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date from</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-40">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date to</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request()->hasAny(['search', 'product_id', 'status', 'date_from', 'date_to', 'branch_id', 'branch']))
                    <a href="{{ route('devices.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($devices as $device)
                    <a href="{{ auth()->user()?->hasPermission('devices.view') ? route('devices.show', $device) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('devices.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary">{{ $device->imei }}</div>
                                <div class="text-sm text-themeBody mt-0.5">{{ $device->product->name ?? '—' }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $device->branch ? $device->branch->name : '—' }} · {{ $device->customer ? $device->customer->name : '—' }}</div>
                                <div class="text-xs text-themeMuted mt-0.5">{{ $device->created_at?->format('M j, Y') ?? '—' }}</div>
                            </div>
                            <div class="flex-shrink-0">
                                @php
                                    $statusClass = $device->status === 'sold' ? 'bg-emerald-100 text-emerald-800' : ($device->status === 'assigned' ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody');
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $statusClass }}">{{ ucfirst($device->status) }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No devices found.</div>
                @endforelse
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                IMEI</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date added</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Sold by</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Sold date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($devices as $device)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $device->imei }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $device->product->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $device->branch ? $device->branch->name : '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $device->customer ? $device->customer->name : '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusClass =
                                            $device->status === 'sold'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : ($device->status === 'assigned'
                                                    ? 'bg-sky-100 text-sky-800'
                                                    : 'bg-themeHover text-themeBody');
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst($device->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">
                                        {{ $device->created_at?->format('M j, Y') ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">
                                        {{ $device->sale?->soldBy?->name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">
                                        {{ $device->sale?->created_at?->format('M j, Y') ?? '—' }}
                                    </div>
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
                                                @if (auth()->user()?->hasPermission('devices.view'))
                                                    <a href="{{ route('devices.show', $device) }}"
                                                        class="block px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
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
                                                @if (!$device->isSold())
                                                    @if (auth()->user()?->hasPermission('sales.create'))
                                                        <a href="{{ route('devices.mark-sold.form', $device) }}"
                                                            class="block px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                                            </svg>
                                                            <span>Sale</span>
                                                        </a>
                                                    @endif
                                                    @if (auth()->user()?->hasPermission('devices.update'))
                                                        <a href="{{ route('devices.edit', $device) }}"
                                                            class="block px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
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
                                                    @if (auth()->user()?->hasPermission('devices.delete'))
                                                        <form action="{{ route('devices.delete', $device) }}"
                                                            method="POST" onsubmit="return confirm('Are you sure?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="w-full text-left px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition flex items-center space-x-2">
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
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-themeMuted font-medium">No devices
                                    found.</td>
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

        <!-- Reconcile IMEIs modal -->
        @if (auth()->user()?->hasPermission('devices.view'))
            <div id="reconcile-imei-modal"
                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="document.getElementById('reconcile-imei-modal').classList.add('hidden')">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-lg w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Reconcile IMEIs</h2>
                        <button type="button"
                            onclick="document.getElementById('reconcile-imei-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-themeMuted mb-4">Upload a file of <strong>valid IMEI numbers</strong>. Devices in the database whose IMEI is <strong>not</strong> in the file will be removed. Choose to reconcile all products (general) or one product only.</p>
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-4"><strong>Protected:</strong> Sold devices and devices attached to a sale are <strong>never</strong> deleted.</p>
                    <form method="POST" action="{{ route('devices.reconcile-imei') }}" enctype="multipart/form-data"
                        class="space-y-4" id="reconcile-imei-form" onsubmit="return confirm('Run reconciliation? Devices not in your file will be deleted. Sold devices and devices on a sale will not be touched.');">
                        @csrf
                        <div>
                            <span class="block text-sm font-medium text-themeBody mb-2">Scope</span>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="scope" value="general" {{ old('scope', 'general') === 'general' ? 'checked' : '' }}
                                        onchange="document.getElementById('reconcile-product-wrap').classList.add('hidden')">
                                    <span class="text-sm font-medium text-themeBody">General (all products)</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="scope" value="product" {{ old('scope') === 'product' ? 'checked' : '' }}
                                        onchange="document.getElementById('reconcile-product-wrap').classList.remove('hidden')">
                                    <span class="text-sm font-medium text-themeBody">Per product</span>
                                </label>
                            </div>
                            @error('scope')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div id="reconcile-product-wrap" class="{{ old('scope') === 'product' ? '' : 'hidden' }}">
                            <label for="reconcile_product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                            <select name="product_id" id="reconcile_product_id"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">— Select product —</option>
                                @foreach ($products ?? [] as $p)
                                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-themeMuted">Only devices of this product will be checked against your file.</p>
                            @error('product_id')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="reconcile_imei_file" class="block text-sm font-medium text-themeBody mb-2">File of valid IMEIs</label>
                            <input type="file" name="file" id="reconcile_imei_file" required
                                accept=".csv,.txt,.xlsx,.xls"
                                class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                            <p class="mt-1 text-xs text-themeMuted">CSV, Excel, or plain text. One IMEI per line or comma-separated. <a href="{{ route('devices.reconcile-imei.sample') }}" class="text-primary hover:underline font-medium">Download sample</a> (1 column: imei).</p>
                            @error('file')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center space-x-3 pt-2">
                            <button type="submit"
                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <span>Run reconciliation</span>
                            </button>
                            <button type="button"
                                onclick="document.getElementById('reconcile-imei-modal').classList.add('hidden')"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Import devices modal -->
        @if (auth()->user()?->hasPermission('devices.create'))
            <div id="import-devices-modal"
                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="document.getElementById('import-devices-modal').classList.add('hidden')">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Import devices</h2>
                        <button type="button"
                            onclick="document.getElementById('import-devices-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('devices.import.submit') }}" enctype="multipart/form-data"
                        class="space-y-4">
                        @csrf
                        <div>
                            <label for="import_branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                            <select name="branch_id" id="import_branch_id" required
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                @foreach ($branches ?? [] as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id', auth()->user()?->branch_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-themeMuted">Devices will be added to this branch. Default is your branch.</p>
                        </div>
                        <div>
                            <label for="import_product_id" class="block text-sm font-medium text-themeBody mb-2">Product
                                (default when not in file)</label>
                            <select name="product_id" id="import_product_id"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">None — use product from file each row</option>
                                @foreach ($products ?? [] as $p)
                                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-themeMuted">Required when entering IMEIs manually. Optional when
                                uploading a file (used as default if row has no product).</p>
                        </div>
                        <div>
                            <label for="import_imeis" class="block text-sm font-medium text-themeBody mb-2">IMEI numbers
                                (optional)</label>
                            <textarea name="imeis" id="import_imeis" rows="4" maxlength="10000"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading text-sm resize-y"
                                placeholder="One IMEI per line or comma-separated"></textarea>
                            <div class="mt-2">
                                <label for="import_imei_file" class="block text-xs font-medium text-themeBody mb-1">Or
                                    upload CSV/Excel</label>
                                <input type="file" name="file" id="import_imei_file" accept=".csv,.xlsx,.xls"
                                    class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                                <p class="mt-1 text-xs text-themeMuted">
                                    <a href="{{ route('devices.import.sample') }}"
                                        class="text-primary hover:underline font-medium">Sample (IMEI only)</a> — header:
                                    <code class="text-themeBody">imei</code>.
                                    <a href="{{ route('devices.import.sample-full') }}"
                                        class="text-primary hover:underline font-medium ml-1">Sample (full)</a> —
                                    optional: product_sku, product_name, brand, branch, status, customer, field_agent,
                                    notes.
                                </p>
                            </div>
                            <p class="mt-1 text-xs text-themeMuted">Each IMEI must be unique. Any column can be omitted;
                                defaults apply when missing.</p>
                        </div>
                        <div class="flex items-center space-x-3 pt-4">
                            <button type="submit"
                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <span>Import</span>
                            </button>
                            <button type="button"
                                onclick="document.getElementById('import-devices-modal').classList.add('hidden')"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

