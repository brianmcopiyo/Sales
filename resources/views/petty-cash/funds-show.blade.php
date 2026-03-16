@extends('layouts.app')

@section('title', 'Fund Details')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Fund Details</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $pettyCashFund->branch->name }}</p>
            </div>
            <a href="{{ route('petty-cash.funds.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Manage Funds</span>
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Current Balance</div>
                <div class="text-2xl font-semibold text-primary">{{ $pettyCashFund->currency }} {{ number_format($pettyCashFund->current_balance, 2) }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Fund Limit (Imprest)</div>
                <div class="text-2xl font-semibold text-themeBody">{{ $pettyCashFund->currency }} {{ number_format($pettyCashFund->fund_limit, 2) }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Custodian</div>
                <div class="text-base font-medium text-themeHeading">{{ $pettyCashFund->custodian?->name ?? '—' }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                @if($pettyCashFund->is_active)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-themeInput text-themeMuted">Inactive</span>
                @endif
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Actions</h2>
            <div class="flex flex-wrap gap-3">
                @if($canManageFunds ?? false)
                    <a href="{{ route('petty-cash.funds.edit', $pettyCashFund) }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Edit fund
                    </a>
                @endif
                @if($canReplenish ?? false)
                    <a href="{{ route('petty-cash.replenish.form', $pettyCashFund) }}"
                        class="bg-amber-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-700 transition shadow-sm">
                        Replenish
                    </a>
                @endif
                <a href="{{ route('petty-cash.reconciliation', $pettyCashFund) }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition border border-themeBorder">
                    Reconcile
                </a>
                <a href="{{ route('petty-cash.index', ['branch_id' => $pettyCashFund->branch_id]) }}"
                    class="text-primary font-medium hover:underline">
                    View requests for this fund
                </a>
            </div>
        </div>

        <div class="space-y-6">
            <h2 class="text-lg font-semibold text-primary tracking-tight">Activity history</h2>

            <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h3 class="text-base font-semibold text-themeHeading p-6 pb-2">Recent disbursements</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Requested by</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Disbursed by</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @forelse($disbursedRequests as $req)
                                <tr class="hover:bg-themeInput/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $req->disbursed_at?->format('M d, Y') ?? '—' }}</div>
                                        <div class="text-sm font-medium text-themeMuted">{{ $req->disbursed_at?->format('h:i A') ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-amber-600">{{ $pettyCashFund->currency }} {{ number_format($req->amount, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeBody">{{ $req->category_name ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $req->requestedByUser?->name ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $req->disbursedByUser?->name ?? '—' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-themeMuted font-medium">No disbursements yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h3 class="text-base font-semibold text-themeHeading p-6 pb-2">Recent replenishments</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">By</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Reference / Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-themeCard divide-y divide-themeBorder">
                            @forelse($replenishments as $r)
                                <tr class="hover:bg-themeInput/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $r->created_at->format('M d, Y') }}</div>
                                        <div class="text-sm font-medium text-themeMuted">{{ $r->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-amber-600">{{ $pettyCashFund->currency }} {{ number_format($r->amount, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-themeHeading">{{ $r->replenishedByUser?->name ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-themeBody">{{ $r->reference ?: $r->notes ?: '—' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-themeMuted font-medium">No replenishments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
