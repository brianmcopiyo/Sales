@extends('layouts.app')

@section('title', 'Stock Transfers')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-operations.index'),
            'label' => 'Back to Stock Operations',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Transfers</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Move stock between branches</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('stock-transfers.view'))
                    <a href="{{ route('stock-transfers.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('stock-transfers.create'))
                    <a href="{{ route('stock-transfers.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Transfer</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <a href="{{ route('stock-transfers.index') }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('status') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="transfers-all">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Transfers</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['total'] }}</div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'pending']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'pending' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="transfers-pending">
                <div class="text-xs font-medium text-themeMuted mb-1">Pending</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['pending'] }}</div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'in_transit']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'in_transit' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="transfers-in-transit">
                <div class="text-xs font-medium text-themeMuted mb-1">In Transit</div>
                <div class="text-2xl font-semibold text-sky-600 tracking-tight">{{ $stats['in_transit'] }}</div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'pending_sender_confirmation']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'pending_sender_confirmation' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="transfers-pending-sender-confirmation">
                <div class="text-xs font-medium text-themeMuted mb-1">Awaiting sender</div>
                <div class="text-2xl font-semibold text-orange-600 tracking-tight">
                    {{ $stats['pending_sender_confirmation'] ?? 0 }}</div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'received']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'received' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="transfers-received">
                <div class="text-xs font-medium text-themeMuted mb-1">Received</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['received'] }}</div>
            </a>
            <a href="{{ route('stock-transfers.index', ['status' => 'rejected']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'rejected' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="transfers-rejected">
                <div class="text-xs font-medium text-themeMuted mb-1">Rejected</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['rejected'] ?? 0 }}</div>
            </a>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('stock-transfers.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-44">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit
                        </option>
                        <option value="pending_sender_confirmation"
                            {{ request('status') === 'pending_sender_confirmation' ? 'selected' : '' }}>Awaiting sender
                        </option>
                        <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                    </select>
                </div>
                @if (auth()->user()?->isAdmin())
                    <div class="w-48">
                        <label for="from_branch_id" class="block text-sm font-medium text-themeBody mb-2">From
                            Branch</label>
                        <select id="from_branch_id" name="from_branch_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All</option>
                            @foreach ($branches ?? [] as $b)
                                <option value="{{ $b->id }}"
                                    {{ request('from_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-48">
                        <label for="to_branch_id" class="block text-sm font-medium text-themeBody mb-2">To Branch</label>
                        <select id="to_branch_id" name="to_branch_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All</option>
                            @foreach ($branches ?? [] as $b)
                                <option value="{{ $b->id }}"
                                    {{ request('to_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
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
                @if (request()->hasAny(['status', 'from_branch_id', 'to_branch_id', 'date_from', 'date_to']))
                    <a href="{{ route('stock-transfers.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Pending Incoming Transfers Alert -->
        @php
            $pendingIncoming = $transfers->filter(function ($transfer) {
                return auth()->user()->branch_id == $transfer->to_branch_id &&
                    in_array($transfer->status, ['pending', 'in_transit']);
            });
        @endphp
        @if ($pendingIncoming->count() > 0)
            <div
                class="bg-sky-50 border border-sky-100 rounded-2xl p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-sky-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-sky-900 mb-1">You have {{ $pendingIncoming->count() }}
                            pending
                            incoming transfer(s)</h3>
                        <p class="text-xs font-medium text-sky-700">Click "Receive" on any transfer below to accept it into
                            your branch stock.</p>
                    </div>
                </div>
            </div>
        @endif

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($transfers as $tf)
                    <a href="{{ route('stock-transfers.show', $tf) }}" class="block px-4 py-4 hover:bg-themeInput/50 transition-colors">
                        <div class="text-sm font-semibold text-primary">{{ $tf->product?->name ?? '—' }}</div>
                        <div class="text-xs text-themeBody mt-0.5">{{ $tf->fromBranch?->name ?? '—' }} → {{ $tf->toBranch?->name ?? '—' }}</div>
                        <div class="text-xs text-themeMuted mt-1">Qty: {{ $tf->quantity ?? 0 }} · {{ $tf->created_at?->format('M d, Y') ?? '—' }}</div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No transfers found.</div>
                @endforelse
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                From</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                To
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Sent by</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Received by</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Rejected by</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Created</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder" id="transfers-table-body">
                        @forelse($transfers as $transfer)
                            @php
                                $isIncoming = auth()->user()->branch_id == $transfer->to_branch_id;
                                $isOutgoing = auth()->user()->branch_id == $transfer->from_branch_id;
                            @endphp
                            <tr
                                class="hover:bg-themeInput/50 transition-colors {{ $isIncoming && in_array($transfer->status, ['pending', 'in_transit']) ? 'bg-sky-50/50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $transfer->product->name }}
                                    </div>
                                    @if ($isIncoming)
                                        <div class="text-xs font-medium text-sky-600 mt-1">Incoming</div>
                                    @elseif($isOutgoing)
                                        <div class="text-xs font-medium text-themeMuted mt-1">Outgoing</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $transfer->fromBranch->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $transfer->toBranch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $transfer->quantity }}</div>
                                    @if ($transfer->status === 'received')
                                        <div class="text-xs text-themeMuted">received:
                                            {{ $transfer->effective_quantity_received }} of {{ $transfer->quantity }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 text-amber-800',
                                            'in_transit' => 'bg-sky-100 text-sky-800',
                                            'received' => 'bg-emerald-100 text-emerald-800',
                                            'cancelled' => 'bg-themeHover text-themeHeading',
                                            'rejected' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $statusColors[$transfer->status] ?? 'bg-themeHover text-themeHeading' }}">
                                        {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $transfer->creator->name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($transfer->receiver)
                                        <div class="text-sm font-medium text-themeBody">{{ $transfer->receiver->name }}
                                        </div>
                                        @if ($transfer->received_at)
                                            <div class="text-xs text-themeMuted">
                                                {{ $transfer->received_at->format('M d, Y H:i') }}</div>
                                        @endif
                                    @else
                                        <span class="text-sm text-themeMuted">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($transfer->rejectedByUser)
                                        <div class="flex items-center gap-2">
                                            <x-profile-picture :user="$transfer->rejectedByUser" size="xs" />
                                            <div>
                                                <div class="text-sm font-medium text-themeBody">
                                                    {{ $transfer->rejectedByUser->name }}</div>
                                                @if ($transfer->rejected_at)
                                                    <div class="text-xs text-themeMuted">
                                                        {{ $transfer->rejected_at->format('M d, Y H:i') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-themeMuted">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $transfer->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        @if (in_array($transfer->status, ['pending', 'in_transit']) &&
                                                auth()->user()->branch_id == $transfer->to_branch_id &&
                                                auth()->user()?->hasPermission('stock-transfers.receive'))
                                            <a href="{{ route('stock-transfers.show', $transfer) }}"
                                                class="inline-flex items-center space-x-1 bg-primary text-white px-3 py-1.5 rounded-xl font-medium hover:bg-primary-dark transition text-xs shadow-sm">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>Approve</span>
                                            </a>
                                        @endif
                                        @if (
                                            $transfer->status === 'pending_sender_confirmation' &&
                                                auth()->user()->branch_id == $transfer->from_branch_id &&
                                                auth()->user()?->hasPermission('stock-transfers.receive'))
                                            <a href="{{ route('stock-transfers.show', $transfer) }}"
                                                class="inline-flex items-center space-x-1 bg-orange-500 text-white px-3 py-1.5 rounded-xl font-medium hover:bg-orange-600 transition text-xs shadow-sm">
                                                <span>Confirm</span>
                                            </a>
                                        @endif
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
                                                    @if (auth()->user()?->hasPermission('stock-transfers.view'))
                                                        <a href="{{ route('stock-transfers.show', $transfer) }}"
                                                            class="block px-4 py-2 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-themeMuted font-medium">No stock
                                    transfers found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                {{ $transfers->links() }}
            </div>
        </div>
    </div>

@endsection
