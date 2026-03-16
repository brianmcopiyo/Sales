@extends('layouts.app')

@section('title', 'Agent Dashboard')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Agent Dashboard</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Your stock, requests, and quick actions</p>
            </div>
            @if ($branch)
                <a href="{{ route('agent-stock-requests.create') }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Request stock from branch
                </a>
            @endif
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total allocated</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ number_format($totalAllocated) }}</div>
                <div class="mt-1 text-xs text-themeMuted">units</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Products</div>
                <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $productCount }}</div>
                <div class="mt-1 text-xs text-themeMuted">with stock</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Low stock</div>
                <div
                    class="text-2xl font-semibold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-themeHeading' }} tracking-tight">
                    {{ $lowStockCount }}</div>
                <div class="mt-1 text-xs text-themeMuted">need top-up</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Out of stock</div>
                <div
                    class="text-2xl font-semibold {{ $outOfStockCount > 0 ? 'text-red-600' : 'text-themeHeading' }} tracking-tight">
                    {{ $outOfStockCount }}</div>
                <div class="mt-1 text-xs text-themeMuted">request more</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Requests pending</div>
                <div
                    class="text-2xl font-semibold {{ $requestPending > 0 ? 'text-amber-600' : 'text-themeHeading' }} tracking-tight">
                    {{ $requestPending }}</div>
                <div class="mt-1 text-xs text-themeMuted">
                    <a href="{{ route('agent-stock-requests.index', ['tab' => 'my-requests']) }}"
                        class="text-primary hover:underline">View requests</a>
                </div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Sales this month</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $salesThisMonth }}</div>
                <div class="mt-1 text-xs text-themeMuted">items sold</div>
            </div>
        </div>

        <!-- My allocations -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div
                class="px-6 py-4 border-b border-themeBorder bg-themeInput/80 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary tracking-tight">My stock allocations</h2>
                    <p class="text-sm text-themeMuted mt-0.5">Stock assigned to you by {{ $branch->name ?? 'your branch' }}
                    </p>
                </div>
                <a href="{{ route('agent-stock-requests.index') }}"
                    class="text-sm font-medium text-primary hover:underline inline-flex items-center gap-1">
                    Manage requests
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                @if ($allocations->isNotEmpty())
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-themeMuted uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-themeMuted uppercase tracking-wider">
                                    Branch</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-themeMuted uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-themeMuted uppercase tracking-wider">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-themeBorder bg-themeCard">
                            @foreach ($allocations as $alloc)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-themeHeading">{{ $alloc->product->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-themeBody">{{ $alloc->branch->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-themeHeading">{{ $alloc->quantity }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($alloc->isOutOfStock())
                                            <span
                                                class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg bg-red-100 text-red-800">Out
                                                of stock</span>
                                        @elseif($alloc->isLowStock())
                                            <span
                                                class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg bg-amber-100 text-amber-800">Low
                                                stock</span>
                                        @else
                                            <span
                                                class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-800">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="px-6 py-12 text-center text-themeMuted">
                        <p class="font-medium">No stock allocated yet.</p>
                        <p class="text-sm mt-1">Request stock from your branch to get started.</p>
                        @if ($branch)
                            <a href="{{ route('agent-stock-requests.create') }}"
                                class="inline-flex items-center gap-2 mt-4 text-primary font-medium hover:underline">
                                Request stock from branch
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent requests & quick links -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent stock requests</h2>
                    <p class="text-sm text-themeMuted mt-0.5">Latest requests to your branch</p>
                </div>
                <div class="p-6">
                    @if ($recentRequests->isNotEmpty())
                        <ul class="space-y-3">
                            @foreach ($recentRequests as $req)
                                <li
                                    class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-themeBorder last:border-0">
                                    <div>
                                        <span class="font-medium text-themeHeading">{{ $req->product->name ?? '—' }}</span>
                                        <span class="text-sm text-themeMuted"> · {{ $req->quantity_requested }}
                                            requested</span>
                                        @if ($req->quantity_fulfilled > 0)
                                            <span class="text-sm text-amber-600"> · {{ $req->quantity_fulfilled }}
                                                fulfilled</span>
                                        @endif
                                    </div>
                                    <span
                                        class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg
                                        {{ $req->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $req->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ in_array($req->status, ['pending', 'partially_fulfilled']) ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $req->isClosed() && $req->status !== 'approved' ? 'bg-themeHover text-themeHeading' : '' }}">
                                        {{ $req->isClosed() && $req->status !== 'approved' ? 'Closed' : ucfirst(str_replace('_', ' ', $req->status)) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('agent-stock-requests.index', ['tab' => 'my-requests']) }}"
                            class="mt-4 inline-flex text-sm font-medium text-primary hover:underline">View all
                            requests</a>
                    @else
                        <p class="text-themeMuted text-sm">No requests yet. <a
                                href="{{ route('agent-stock-requests.create') }}"
                                class="text-primary hover:underline">Request stock</a></p>
                    @endif
                </div>
            </div>

            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Quick actions</h2>
                    <p class="text-sm text-themeMuted mt-0.5">Shortcuts for your workflow</p>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('agent-stock-requests.create') }}"
                        class="flex items-center gap-3 p-4 rounded-xl border border-themeBorder hover:border-[#006F78]/30 hover:bg-primary/5 transition">
                        <div class="bg-primary/10 rounded-xl p-3">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-themeHeading">Request stock from branch</div>
                            <div class="text-sm text-themeMuted">Ask for more stock when you're running low</div>
                        </div>
                        <svg class="w-5 h-5 text-themeMuted ml-auto" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                    <a href="{{ route('agent-stock-requests.index') }}"
                        class="flex items-center gap-3 p-4 rounded-xl border border-themeBorder hover:border-[#006F78]/30 hover:bg-primary/5 transition">
                        <div class="bg-sky-100 rounded-xl p-3">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2v14">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-themeHeading">My stock requests</div>
                            <div class="text-sm text-themeMuted">View status of pending, approved, and rejected requests
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-themeMuted ml-auto" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                    @if ($canViewCommissions)
                        <a href="{{ route('commission-disbursements.index') }}"
                            class="flex items-center gap-3 p-4 rounded-xl border border-themeBorder hover:border-[#006F78]/30 hover:bg-primary/5 transition">
                            <div class="bg-emerald-100 rounded-xl p-3">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v2m-7-6a7 7 0 1114 0 7 7 0 01-14 0z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-themeHeading">My commissions</div>
                                <div class="text-sm text-themeMuted">View and track your commission disbursements</div>
                            </div>
                            <svg class="w-5 h-5 text-themeMuted ml-auto" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
