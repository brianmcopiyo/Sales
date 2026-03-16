@extends('layouts.app')

@section('title', 'Stock Management')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Management</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Restock, transfer, and track stock across branches</p>
            </div>
            <div class="flex items-center space-x-3">
                @if (auth()->user()?->hasPermission('stock-management.view'))
                    <form method="POST" action="{{ route('stock-management.sync-stock-from-devices') }}" class="inline"
                        onsubmit="return confirm('Set all branch stock to the count of available (non-sold) devices per branch/product?');">
                        @csrf
                        <button type="submit"
                            class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            <span>Sync stock from devices</span>
                        </button>
                    </form>
                    <a href="{{ route('stock-management.reconciliation') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                        <span>Stock Reconciliation</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('stock-management.restock') || auth()->user()?->hasPermission('stock-management.initiate-restock'))
                    <button onclick="document.getElementById('create-order-modal').classList.remove('hidden')"
                        class="bg-amber-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-600 transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                        <span>Create Restock Order</span>
                    </button>
                @endif
                @if (auth()->user()->branch_id)
                    <a href="{{ route('stock-requests.index') }}"
                        class="bg-sky-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-sky-700 transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span>Request stock</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('stock-transfers.create'))
                    <a href="{{ route('stock-transfers.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span>Create Transfer</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Pending Approvals</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['pending_approvals'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Delivered Today</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['delivered_today'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">In Store</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['in_store_total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">In Transit</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['in_transit_total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Sold Today</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $stats['sold_today'] }}</div>
            </div>
        </div>

        <!-- Pending Restock Orders (grouped by order / batch) -->
        @if ($pendingRestockOrders->count() > 0 && (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager()))
            @php $pendingGrouped = $pendingRestockOrders->groupBy(fn($o) => $o->order_batch ?? $o->order_number); @endphp
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Pending Restock Orders</h2>
                <p class="text-sm text-themeMuted mb-4">Receive stock when it arrives to update inventory and reconcile
                    orders.</p>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach ($pendingGrouped as $displayNum => $orders)
                        @php $first = $orders->first(); @endphp
                        <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    @if ($orders->count() > 1)
                                        <div class="font-medium text-themeHeading">{{ $orders->count() }} products</div>
                                    @else
                                        <div class="font-medium text-themeHeading">{{ $first->product->name }}</div>
                                    @endif
                                    <a href="{{ route('stock-management.orders.show', $first) }}"
                                        class="text-sm font-medium text-primary hover:underline">{{ $displayNum }}</a>
                                </div>
                                <span
                                    class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $first->status === 'received_partial' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $first->status === 'received_partial' ? 'Partial' : 'Pending' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm font-medium text-themeBody mb-3">
                                <div><span class="text-themeMuted">Branch:</span> {{ $first->branch->name }}</div>
                                @if ($orders->count() === 1)
                                    <div><span class="text-themeMuted">Ordered:</span> {{ $first->quantity_ordered }}</div>
                                    <div><span class="text-themeMuted">Received:</span> {{ $first->quantity_received }}
                                    </div>
                                    <div><span class="text-themeMuted">Outstanding:</span>
                                        {{ $first->quantity_outstanding }}
                                    </div>
                                @else
                                    <div><span class="text-themeMuted">Lines:</span> {{ $orders->count() }}</div>
                                @endif
                                @if ($first->reference_number)
                                    <div class="col-span-2"><span class="text-themeMuted">Reference:</span>
                                        {{ $first->reference_number }}</div>
                                @endif
                                @if ($first->dealership_display_name)
                                    <div class="col-span-2"><span class="text-themeMuted">Dealership:</span>
                                        {{ $first->dealership_display_name }}</div>
                                @endif
                                @if ($first->expected_at)
                                    <div><span class="text-themeMuted">Expected:</span>
                                        {{ $first->expected_at->format('M d, Y') }}</div>
                                @endif
                                @if ($orders->count() === 1 && $first->total_acquisition_cost !== null)
                                    <div class="col-span-2"><span class="text-themeMuted">Total acquisition cost:</span>
                                        {{ number_format($first->total_acquisition_cost, 2) }}</div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($orders->count() === 1)
                                    @if ($first->quantity_outstanding > 0)
                                        <button type="button"
                                            onclick="openApproveModal('{{ $first->id }}', '{{ addslashes($first->product->name) }}', '{{ addslashes($first->display_order_number) }}', {{ $first->quantity_outstanding }})"
                                            class="inline-flex items-center space-x-2 bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>Approve</span>
                                        </button>
                                        <button type="button"
                                            onclick="openReceiveModal('{{ $first->id }}', '{{ addslashes($first->product->name) }}', '{{ addslashes($first->display_order_number) }}', {{ $first->quantity_outstanding }})"
                                            class="inline-flex items-center space-x-2 bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                                                </path>
                                            </svg>
                                            <span>Partially approve</span>
                                        </button>
                                    @endif
                                    @if ($first->canBeRejected())
                                        <button type="button"
                                            onclick="openRejectModal('{{ $first->id }}', '{{ addslashes($first->product->name) }}', '{{ addslashes($first->display_order_number) }}')"
                                            class="inline-flex items-center space-x-2 bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            <span>Reject</span>
                                        </button>
                                    @endif
                                @else
                                    <a href="{{ route('stock-management.orders.show', $first) }}"
                                        class="inline-flex items-center space-x-2 bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                                        <span>View {{ $orders->count() }} lines</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pending Approvals -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Pending Approvals</h2>
                @if ($pendingTransfers->count() > 0)
                    <div class="space-y-3">
                        @foreach ($pendingTransfers as $transfer)
                            <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-medium text-themeHeading">{{ $transfer->product->name }}</div>
                                        <div class="text-sm font-medium text-themeMuted">{{ $transfer->transfer_number }}
                                        </div>
                                    </div>
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Pending</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm mb-3 font-medium text-themeBody">
                                    <div><span class="text-themeMuted">From:</span> {{ $transfer->fromBranch->name }}
                                    </div>
                                    <div><span class="text-themeMuted">To:</span> {{ $transfer->toBranch->name }}</div>
                                    <div><span class="text-themeMuted">Quantity:</span> {{ $transfer->quantity }}</div>
                                    <div><span class="text-themeMuted">Created:</span>
                                        {{ $transfer->created_at->format('M d, Y') }}</div>
                                </div>
                                @if (auth()->user()->branch_id == $transfer->to_branch_id && auth()->user()?->hasPermission('stock-transfers.receive'))
                                    <a href="{{ route('stock-transfers.show', $transfer) }}"
                                        class="inline-flex items-center space-x-2 bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Approve</span>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-themeMuted font-medium py-8">No pending approvals</div>
                @endif
            </div>

            <!-- In Transit Stocks -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">In Transit Stocks</h2>
                @if ($inTransitStocks->count() > 0)
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach ($inTransitStocks as $transfer)
                            <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-medium text-themeHeading">{{ $transfer->product->name }}</div>
                                        <div class="text-sm font-medium text-themeMuted">{{ $transfer->transfer_number }}
                                        </div>
                                    </div>
                                    <span
                                        class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $transfer->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm font-medium text-themeBody">
                                    <div><span class="text-themeMuted">From:</span> {{ $transfer->fromBranch->name }}
                                    </div>
                                    <div><span class="text-themeMuted">To:</span> {{ $transfer->toBranch->name }}</div>
                                    <div><span class="text-themeMuted">Quantity:</span> {{ $transfer->quantity }}</div>
                                    <div><span class="text-themeMuted">Created:</span>
                                        {{ $transfer->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-themeMuted font-medium py-8">No stocks in transit</div>
                @endif
            </div>
        </div>

        <!-- Sold Stocks -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Sold Stocks</h2>
            @if ($soldStocks->count() > 0)
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach ($soldStocks as $item)
                        <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-medium text-themeHeading">{{ $item->product->name }}</div>
                                    <div class="text-sm font-medium text-themeMuted">{{ $item->sale->sale_number }}
                                    </div>
                                </div>
                                <span
                                    class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">Sold</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm font-medium text-themeBody">
                                <div><span class="text-themeMuted">Quantity:</span> {{ $item->quantity }}</div>
                                <div><span class="text-themeMuted">Price:</span> TSh
                                    {{ number_format($item->unit_price, 2) }}</div>
                                <div><span class="text-themeMuted">Branch:</span> {{ $item->sale->branch->name }}</div>
                                <div><span class="text-themeMuted">Date:</span>
                                    {{ $item->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-themeMuted font-medium py-8">No sold stocks</div>
            @endif
        </div>

        <!-- History: restocks and stock transfers (always at bottom) -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div
                class="px-6 py-4 border-b border-themeBorder bg-themeInput/80 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-primary tracking-tight">History</h2>
                    <p class="text-sm text-themeMuted mt-0.5">Restocks and stock transfers</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @if (auth()->user()->hasPermission('stock-management.view'))
                        <a href="{{ route('stock-management.restock-orders.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark flex items-center space-x-1">
                            <span>View all restocks</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </a>
                    @endif
                    @if (auth()->user()->hasPermission('stock-transfers.view'))
                        <a href="{{ route('stock-transfers.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark flex items-center space-x-1">
                            <span>View all transfers</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Stock transfers -->
                    <div>
                        <h3 class="text-base font-semibold text-themeHeading tracking-tight mb-3">Stock Transfers</h3>
                        @if ($transferHistory->count() > 0)
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @foreach ($transferHistory as $transfer)
                                    <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <div class="font-medium text-themeHeading">{{ $transfer->product->name }}
                                                </div>
                                                <div class="text-sm font-medium text-themeMuted">
                                                    {{ $transfer->transfer_number }}</div>
                                            </div>
                                            @if ($transfer->status === 'received')
                                                <span
                                                    class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">Delivered</span>
                                            @elseif ($transfer->status === 'in_transit')
                                                <span
                                                    class="px-2.5 py-1 text-xs rounded-lg font-medium bg-sky-100 text-sky-800">In
                                                    transit</span>
                                            @else
                                                <span
                                                    class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">{{ ucfirst(str_replace('_', ' ', $transfer->status)) }}</span>
                                            @endif
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm font-medium text-themeBody">
                                            <div><span class="text-themeMuted">From:</span>
                                                {{ $transfer->fromBranch->name }}</div>
                                            <div><span class="text-themeMuted">To:</span> {{ $transfer->toBranch->name }}
                                            </div>
                                            <div><span class="text-themeMuted">Quantity:</span>
                                                {{ $transfer->effective_quantity_received ?? $transfer->quantity }}
                                                @if ($transfer->status === 'received')
                                                    of {{ $transfer->quantity }}
                                                @endif
                                            </div>
                                            <div><span class="text-themeMuted">Created:</span>
                                                {{ $transfer->created_at->format('M d, Y') }}</div>
                                            @if ($transfer->status === 'received' && $transfer->received_at)
                                                <div><span class="text-themeMuted">Received:</span>
                                                    {{ $transfer->received_at->format('M d, Y') }}</div>
                                                <div><span class="text-themeMuted">By:</span>
                                                    {{ $transfer->receiver->name ?? '-' }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-themeMuted font-medium py-8">No transfers yet</div>
                        @endif
                    </div>

                    <!-- Restocks -->
                    <div>
                        <h3 class="text-base font-semibold text-themeHeading tracking-tight mb-3">Restocks</h3>
                        @if (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager())
                            @if ($restockHistory->count() > 0)
                                @php $restockHistoryGrouped = $restockHistory->groupBy(fn($o) => $o->order_batch ?? $o->order_number); @endphp
                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    @foreach ($restockHistoryGrouped as $displayNum => $orders)
                                        @php $first = $orders->first(); @endphp
                                        <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    @if ($orders->count() > 1)
                                                        <div class="font-medium text-themeHeading">{{ $orders->count() }}
                                                            products</div>
                                                    @else
                                                        <div class="font-medium text-themeHeading">{{ $first->product->name }}
                                                        </div>
                                                    @endif
                                                    <div class="text-sm font-medium text-themeMuted">{{ $displayNum }}
                                                    </div>
                                                </div>
                                                <span
                                                    class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $first->status === 'received_full' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                                    {{ $first->status === 'received_full' ? 'Fully received' : 'Partial' }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-sm font-medium text-themeBody">
                                                <div><span class="text-themeMuted">Branch:</span>
                                                    {{ $first->branch->name }}
                                                </div>
                                                @if ($orders->count() === 1)
                                                    <div><span class="text-themeMuted">Ordered:</span>
                                                        {{ $first->quantity_ordered }}</div>
                                                    <div><span class="text-themeMuted">Received:</span>
                                                        {{ $first->quantity_received }}</div>
                                                @endif
                                                <div><span class="text-themeMuted">Received at:</span>
                                                    {{ $first->received_at ? $first->received_at->format('M d, Y H:i') : '-' }}
                                                </div>
                                                @if ($first->reference_number)
                                                    <div class="col-span-2"><span
                                                            class="text-themeMuted">Reference:</span>
                                                        {{ $first->reference_number }}</div>
                                                @endif
                                                @if ($first->dealership_display_name)
                                                    <div class="col-span-2"><span class="text-themeMuted">Dealership:</span>
                                                        {{ $first->dealership_display_name }}</div>
                                                @endif
                                                @if ($orders->count() === 1 && $first->total_acquisition_cost !== null)
                                                    <div class="col-span-2"><span class="text-themeMuted">Total
                                                            acquisition
                                                            cost:</span>
                                                        {{ number_format($first->total_acquisition_cost, 2) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-themeMuted font-medium py-8">No restock history yet</div>
                            @endif
                        @else
                            <div class="text-center text-themeMuted font-medium py-8">No restock history</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- In Store Stocks (last on page) -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                <h2 class="text-lg font-semibold text-primary tracking-tight">In Store Stocks</h2>
            </div>
            @if ($inStoreStocks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Branch</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Available</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Minimum Level</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @foreach ($inStoreStocks as $stock)
                                <tr class="hover:bg-themeInput/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $stock->product->name }}</div>
                                        <div class="text-xs font-medium text-themeMuted">{{ $stock->product->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">{{ $stock->branch->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">{{ $stock->display_quantity }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-primary">{{ $stock->available_quantity }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">
                                            {{ $stock->product->minimum_stock_level ?? 10 }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $stock->isLowStock() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $stock->isLowStock() ? 'Low Stock' : 'In Stock' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-themeMuted font-medium py-8">No stocks in store</div>
            @endif
        </div>
    </div>

    <!-- Create Restock Order Modal -->
    @if (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager())
        <div id="create-order-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="document.getElementById('create-order-modal').classList.add('hidden')">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                @click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Create Restock Order</h2>
                    <button onclick="document.getElementById('create-order-modal').classList.add('hidden')"
                        class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-themeMuted mb-4">Add one or more products. Same branch, reference and dealership apply
                    to the whole order.</p>

                <form method="POST" action="{{ route('stock-management.orders.store') }}" id="create-order-form"
                    class="space-y-4">
                    @csrf

                    <div>
                        <label for="order_branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch
                            *</label>
                        @if (auth()->user()->branch_id)
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                            <div
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput font-medium text-themeBody">
                                {{ auth()->user()->branch->name }} ({{ auth()->user()->branch->code }})
                            </div>
                        @else
                            <select name="branch_id" id="order_branch_id" required
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('branch_id') border-red-300 @enderror">
                                <option value="">Select Branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('branch_id')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-themeBody">Products *</label>
                            <button type="button" id="order-add-product"
                                class="text-sm font-medium text-primary hover:underline">+ Add another product</button>
                        </div>
                        <div id="order-product-rows" class="space-y-3">
                            @php
                                $oldProducts = old('product_id', []);
                                $oldQuantities = old('quantity', []);
                                $oldCosts = old('total_acquisition_cost', []);
                                if (empty($oldProducts)) {
                                    $oldProducts = [''];
                                    $oldQuantities = [''];
                                    $oldCosts = [''];
                                }
                            @endphp
                            @foreach ($oldProducts as $idx => $pid)
                                <div
                                    class="order-product-row flex flex-wrap items-end gap-3 p-3 border border-themeBorder rounded-xl bg-themeInput/50">
                                    <div class="flex-1 min-w-[140px]">
                                        <label class="block text-xs font-medium text-themeMuted mb-1">Product</label>
                                        <select name="product_id[]"
                                            class="order-row-product w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading"
                                            {{ $pid === '' && $idx === 0 ? 'required' : '' }}>
                                            <option value="">Select</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    {{ $pid == $product->id ? 'selected' : '' }}>{{ $product->name }}
                                                    ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-24">
                                        <label class="block text-xs font-medium text-themeMuted mb-1">Qty *</label>
                                        <input type="number" name="quantity[]" min="1"
                                            value="{{ $oldQuantities[$idx] ?? '' }}" placeholder="1"
                                            class="order-row-qty w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading"
                                            {{ $idx === 0 ? 'required' : '' }}>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs font-medium text-themeMuted mb-1">Cost (opt)</label>
                                        <input type="number" name="total_acquisition_cost[]" min="0"
                                            step="0.01" value="{{ $oldCosts[$idx] ?? '' }}" placeholder="0.00"
                                            class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button"
                                            class="order-row-remove px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium"
                                            title="Remove line">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-themeMuted">Each product once per order. Total acquisition cost is per
                            line (for accounting).</p>
                        @error('product_id')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        @error('quantity')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="order_reference_number" class="block text-sm font-medium text-themeBody mb-2">Stock
                            reference number (optional)</label>
                        <input type="text" name="reference_number" id="order_reference_number" maxlength="128"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('reference_number') border-red-300 @enderror"
                            value="{{ old('reference_number') }}" placeholder="e.g. PO-12345, invoice ref">
                        @error('reference_number')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
<label for="order_dealership_name" class="block text-sm font-medium text-themeBody mb-2">Dealership
                                (optional)</label>
                        <input type="text" name="dealership_name" id="order_dealership_name" maxlength="255"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('dealership_name') border-red-300 @enderror"
                            value="{{ old('dealership_name') }}" placeholder="Dealership name">
                        @error('dealership_name')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="order_expected_at" class="block text-sm font-medium text-themeBody mb-2">Expected Date
                            (optional)</label>
                        <input type="date" name="expected_at" id="order_expected_at"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('expected_at') border-red-300 @enderror"
                            value="{{ old('expected_at') }}">
                        @error('expected_at')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit"
                            class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Create Order</span>
                        </button>
                        <button type="button"
                            onclick="document.getElementById('create-order-modal').classList.add('hidden')"
                            class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Cancel</span>
                        </button>
                    </div>
                </form>
                <template id="order-product-row-tpl">
                    <div
                        class="order-product-row flex flex-wrap items-end gap-3 p-3 border border-themeBorder rounded-xl bg-themeInput/50">
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-themeMuted mb-1">Product</label>
                            <select name="product_id[]"
                                class="order-row-product w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                                <option value="">Select</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24">
                            <label class="block text-xs font-medium text-themeMuted mb-1">Qty *</label>
                            <input type="number" name="quantity[]" min="1" value="1" placeholder="1"
                                class="order-row-qty w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                        </div>
                        <div class="w-28">
                            <label class="block text-xs font-medium text-themeMuted mb-1">Cost (opt)</label>
                            <input type="number" name="total_acquisition_cost[]" min="0" step="0.01"
                                value="" placeholder="0.00"
                                class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                        </div>
                        <div class="flex items-end">
                            <button type="button"
                                class="order-row-remove px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium"
                                title="Remove line">Remove</button>
                        </div>
                    </div>
                </template>
                <script>
                    (function() {
                        var container = document.getElementById('order-product-rows');
                        var tpl = document.getElementById('order-product-row-tpl');
                        var addBtn = document.getElementById('order-add-product');
                        if (!container || !tpl || !addBtn) return;

                        function syncProductOptions() {
                            var selects = container.querySelectorAll('select.order-row-product');
                            var selectedByRow = [];
                            selects.forEach(function(sel) {
                                selectedByRow.push(sel.value || '');
                            });
                            selects.forEach(function(sel, rowIndex) {
                                var currentVal = selectedByRow[rowIndex];
                                Array.prototype.forEach.call(sel.options, function(opt) {
                                    if (opt.value === '') {
                                        opt.disabled = false;
                                        return;
                                    }
                                    var usedElsewhere = selectedByRow.some(function(v, i) {
                                        return i !== rowIndex && v === opt.value;
                                    });
                                    opt.disabled = usedElsewhere;
                                });
                            });
                        }

                        container.addEventListener('change', function(e) {
                            if (e.target.classList.contains('order-row-product')) syncProductOptions();
                        });
                        syncProductOptions();

                        addBtn.addEventListener('click', function() {
                            var row = tpl.content.cloneNode(true);
                            container.appendChild(row);
                            syncProductOptions();
                        });
                        container.addEventListener('click', function(e) {
                            if (e.target.classList.contains('order-row-remove')) {
                                var row = e.target.closest('.order-product-row');
                                if (row && container.querySelectorAll('.order-product-row').length > 1) {
                                    row.remove();
                                    syncProductOptions();
                                }
                            }
                        });
                        document.getElementById('create-order-form').addEventListener('submit', function() {
                            var rows = container.querySelectorAll('.order-product-row');
                            rows.forEach(function(r, i) {
                                r.querySelectorAll('select[name="product_id[]"], input[name="quantity[]"]').forEach(
                                    function(el) {
                                        el.removeAttribute('required');
                                    });
                                if (i === 0) {
                                    var sel = r.querySelector('select[name="product_id[]"]');
                                    var qty = r.querySelector('input[name="quantity[]"]');
                                    if (sel) sel.setAttribute('required', 'required');
                                    if (qty) qty.setAttribute('required', 'required');
                                }
                            });
                        });
                    })();
                </script>
            </div>
        </div>

        <!-- Receive Order Modal -->
        <div id="receive-order-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="document.getElementById('receive-order-modal').classList.add('hidden')">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                @click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Receive Stock</h2>
                    <button onclick="document.getElementById('receive-order-modal').classList.add('hidden')"
                        class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-themeBody mb-2"><span class="font-medium" id="receive-product-name"></span></p>
                <p class="text-sm text-themeMuted mb-4">Order: <span id="receive-order-number"></span></p>

                <form id="receive-order-form" method="POST" action="" class="space-y-4"
                    enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label for="receive_quantity" class="block text-sm font-medium text-themeBody mb-2">Quantity
                            Received *</label>
                        <input type="number" name="quantity_received" id="receive_quantity" min="1" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <p class="mt-1 text-xs text-themeMuted">Max: <span id="receive-max-qty"></span></p>
                    </div>
                    <div>
                        <label for="receive_notes" class="block text-sm font-medium text-themeBody mb-2">Notes
                            (optional)</label>
                        <textarea name="notes" id="receive_notes" rows="2" maxlength="500"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            placeholder="e.g. partial delivery, batch number"></textarea>
                    </div>
                    <div>
                        <label for="receive_imeis" class="block text-sm font-medium text-themeBody mb-2">IMEI numbers
                            (optional)</label>
                        <textarea name="imeis" id="receive_imeis" rows="3" maxlength="2000"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading text-sm"
                            placeholder="One IMEI per line or comma-separated"></textarea>
                        <div class="mt-2">
                            <label for="receive_imei_file" class="block text-xs font-medium text-themeBody mb-1">Or upload
                                CSV/Excel</label>
                            <input type="file" name="imei_file" id="receive_imei_file" accept=".csv,.xlsx,.xls"
                                class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                            <p class="mt-1 text-xs text-themeMuted">
                                <a href="{{ asset('sample_imei_upload.csv') }}" download
                                    class="text-primary hover:underline font-medium">Download sample CSV</a> — one IMEI
                                per row, header: <code class="text-themeBody">imei</code>.
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-themeMuted">Pasted IMEIs and uploaded CSV/Excel are validated: each must be exactly 15 digits and pass the validity check. Each must be unique.</p>
                        @error('imeis')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-start space-x-3">
                        <input type="checkbox" name="mark_order_complete" id="receive_mark_complete" value="1"
                            class="mt-1 h-4 w-4 rounded border-themeBorder text-primary focus:ring-primary">
                        <label for="receive_mark_complete" class="text-sm text-themeBody">
                            Mark order as complete (no more stock expected). Leave unchecked to keep the order open for more
                            deliveries.
                        </label>
                    </div>
                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit"
                            class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Record Receipt</span>
                        </button>
                        <button type="button"
                            onclick="document.getElementById('receive-order-modal').classList.add('hidden')"
                            class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Approve (full) Order Modal -->
        <div id="approve-order-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="document.getElementById('approve-order-modal').classList.add('hidden')">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                @click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Approve full order</h2>
                    <button onclick="document.getElementById('approve-order-modal').classList.add('hidden')"
                        class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-themeBody mb-2"><span class="font-medium" id="approve-product-name"></span></p>
                <p class="text-sm text-themeMuted mb-4">Order: <span id="approve-order-number"></span></p>

                <form id="approve-order-form" method="POST" action="" class="space-y-4"
                    enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-themeBody mb-2">Quantity to receive</label>
                        <div class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput font-medium text-themeBody"
                            id="approve-quantity-display"></div>
                    </div>
                    <div>
                        <label for="approve_imeis" class="block text-sm font-medium text-themeBody mb-2">IMEI numbers
                            (optional)</label>
                        <textarea name="imeis" id="approve_imeis" rows="3" maxlength="2000"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-themeHeading text-sm"
                            placeholder="One IMEI per line or comma-separated"></textarea>
                        <div class="mt-2">
                            <label for="approve_imei_file" class="block text-xs font-medium text-themeBody mb-1">Or upload
                                CSV/Excel</label>
                            <input type="file" name="imei_file" id="approve_imei_file" accept=".csv,.xlsx,.xls"
                                class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600/10 file:text-emerald-700 file:font-medium hover:file:bg-emerald-600/20">
                            <p class="mt-1 text-xs text-themeMuted">
                                <a href="{{ asset('sample_imei_upload.csv') }}" download
                                    class="text-emerald-600 hover:underline font-medium">Download sample CSV</a> — one IMEI
                                per row, header: <code class="text-themeBody">imei</code>.
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-themeMuted">Pasted IMEIs and uploaded CSV/Excel are validated: each must be exactly 15 digits and pass the validity check. Each must be unique.</p>
                    </div>
                    @error('imeis')
                        <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit"
                            class="flex-1 bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Approve</span>
                        </button>
                        <button type="button"
                            onclick="document.getElementById('approve-order-modal').classList.add('hidden')"
                            class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Order Modal -->
        <div id="reject-order-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="document.getElementById('reject-order-modal').classList.add('hidden')">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                @click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Reject Restock Order</h2>
                    <button onclick="document.getElementById('reject-order-modal').classList.add('hidden')"
                        class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-themeBody mb-2"><span class="font-medium" id="reject-product-name"></span></p>
                <p class="text-sm text-themeMuted mb-4">Order: <span id="reject-order-number"></span></p>

                <form id="reject-order-form" method="POST" action="" class="space-y-4">
                    @csrf
                    <div>
                        <label for="reject_reason" class="block text-sm font-medium text-themeBody mb-2">Reason
                            (optional)</label>
                        <textarea name="rejection_reason" id="reject_reason" rows="3" maxlength="2000"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 font-medium text-themeHeading"
                            placeholder="e.g. order cancelled by dealership"></textarea>
                    </div>
                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit"
                            class="flex-1 bg-red-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-red-700 transition shadow-sm flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Reject order</span>
                        </button>
                        <button type="button"
                            onclick="document.getElementById('reject-order-modal').classList.add('hidden')"
                            class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openReceiveModal(orderId, productName, orderNumber, maxQty) {
                document.getElementById('receive-product-name').textContent = productName;
                document.getElementById('receive-order-number').textContent = orderNumber;
                document.getElementById('receive-max-qty').textContent = maxQty;
                document.getElementById('receive_quantity').setAttribute('max', maxQty);
                document.getElementById('receive_quantity').value = maxQty;
                document.getElementById('receive-order-form').action = '{{ url('stock-management/orders') }}/' + orderId +
                    '/receive';
                document.getElementById('receive-order-modal').classList.remove('hidden');
            }

            function openApproveModal(orderId, productName, orderNumber, quantity) {
                document.getElementById('approve-product-name').textContent = productName;
                document.getElementById('approve-order-number').textContent = orderNumber;
                document.getElementById('approve-quantity-display').textContent = quantity;
                document.getElementById('approve-order-form').action = '{{ url('stock-management/orders') }}/' + orderId +
                    '/approve';
                document.getElementById('approve_imeis').value = '';
                document.getElementById('approve-order-modal').classList.remove('hidden');
            }

            function openRejectModal(orderId, productName, orderNumber) {
                document.getElementById('reject-product-name').textContent = productName;
                document.getElementById('reject-order-number').textContent = orderNumber;
                document.getElementById('reject-order-form').action = '{{ url('stock-management/orders') }}/' + orderId +
                    '/reject';
                document.getElementById('reject_reason').value = '';
                document.getElementById('reject-order-modal').classList.remove('hidden');
            }
        </script>
    @endif
@endsection
