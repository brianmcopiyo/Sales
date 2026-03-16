@extends('layouts.app')

@section('title', 'Petty Cash')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('dashboard'),
            'label' => 'Back to Dashboard',
        ])
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Petty Cash</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Funds, requests, and operational expenses</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if(auth()->user()?->hasPermission('petty-cash.view'))
                    <a href="{{ route('petty-cash.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>
                @endif
                @if(($canRequest ?? false) && !($hasPendingRequest ?? false) && !($hasDisbursedWithoutProof ?? false))
                    <a href="{{ route('petty-cash.request.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Request</span>
                    </a>
                @endif
                @if($canManageFunds ?? false)
                    <a href="{{ route('petty-cash.funds.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v2M7 9h10m-5 0v6m5-6v6" />
                        </svg>
                        <span>Manage Funds</span>
                    </a>
                @endif
            </div>
        </div>

        @if($hasPendingRequest ?? false)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
                You have a pending petty cash request. Submit another only after it is accepted or rejected.
            </div>
        @endif
        @if($hasDisbursedWithoutProof ?? false)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
                You have a disbursed petty cash request. Please <strong>upload proof of expenditure</strong> before you can submit a new request.
                @if(isset($requestNeedingProof))
                    <a href="{{ route('petty-cash.show-request', $requestNeedingProof) }}" class="font-medium underline ml-1">Open request and upload proof →</a>
                @endif
            </div>
        @endif

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Requests</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Pending Approval</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['pending'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Approved (Pending Disburse)</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['approved_pending_disburse'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Disbursed This Month</div>
                <div class="text-2xl font-semibold text-themeBody">TSh {{ number_format($stats['disbursed_this_month'], 2) }}</div>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('petty-cash.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[180px]">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        @foreach($branchesForFilter as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="disbursed" {{ request('status') === 'disbursed' ? 'selected' : '' }}>Disbursed</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="min-w-[140px]">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Filter
                    </button>
                    @if(request()->hasAny(['branch_id','status','date_from','date_to']))
                        <a href="{{ route('petty-cash.index') }}"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($requests as $req)
                    <div class="px-4 py-4 hover:bg-themeInput/50 transition-colors">
                        <a href="{{ route('petty-cash.show-request', $req) }}" class="block">
                            <div class="flex justify-between items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-primary">{{ $req->fund->branch->name }}</div>
                                    <div class="text-xs text-themeBody mt-0.5">{{ $req->category_name ?? '—' }} · {{ $req->requestedByUser->name }}@if($req->approvedByUser) · Approved by {{ $req->approvedByUser->name }}@endif</div>
                                    <div class="text-xs text-themeMuted mt-1">{{ $req->created_at->format('M d, Y') }}</div>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <div class="text-sm font-semibold text-amber-600">{{ $req->fund->currency }} {{ number_format($req->amount, 2) }}</div>
                                    @if($req->status === 'pending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium mt-1 bg-amber-100 text-amber-800">Pending</span>
                                    @elseif($req->status === 'approved')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium mt-1 bg-sky-100 text-sky-800">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium mt-1 bg-red-100 text-red-800">Rejected</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium mt-1 bg-emerald-100 text-emerald-800">Disbursed</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        @if(($canCustodian ?? false) && $req->isApproved() && auth()->id() != $req->requested_by)
                            <div class="mt-2 pt-2 border-t border-themeBorder">
                                <a href="{{ route('petty-cash.show-request', $req) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Mark as paid</a>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No requests found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Requested By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Approver</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($requests as $req)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $req->fund->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-amber-600">{{ $req->fund->currency }} {{ number_format($req->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $req->category_name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $req->requestedByUser->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $req->approvedByUser?->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                    @elseif($req->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-sky-100 text-sky-800">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Disbursed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $req->created_at->format('M d, Y') }}</div>
                                    <div class="text-sm font-medium text-themeMuted">{{ $req->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(($canCustodian ?? false) && $req->isApproved() && auth()->id() != $req->requested_by)
                                        <a href="{{ route('petty-cash.show-request', $req) }}"
                                            class="font-medium text-emerald-600 hover:text-emerald-700 mr-3">Mark as paid</a>
                                    @endif
                                    <a href="{{ route('petty-cash.show-request', $req) }}"
                                        class="font-medium text-primary hover:text-primary-dark">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-themeMuted font-medium">No requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>

        @if($funds->isNotEmpty())
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Funds by branch</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($funds as $fund)
                        <div class="border border-themeBorder rounded-xl p-4 flex justify-between items-center">
                            <div>
                                <a href="{{ route('petty-cash.funds.show', $fund) }}" class="font-medium text-primary hover:text-primary-dark hover:underline">{{ $fund->branch->name }}</a>
                                <div class="text-xs text-themeMuted mt-0.5">Click to view details</div>
                                <div class="text-sm text-themeMuted mt-1">Custodian: {{ $fund->custodian?->name ?? '—' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-primary">{{ $fund->currency }} {{ number_format($fund->current_balance, 2) }}</div>
                                <div class="text-xs text-themeMuted">Limit: {{ number_format($fund->fund_limit, 2) }}</div>
                                <div class="flex gap-2 mt-2">
                                    @if(($canReplenish ?? false))
                                        <a href="{{ route('petty-cash.replenish.form', $fund) }}"
                                            class="text-xs font-medium text-primary hover:underline">Replenish</a>
                                    @endif
                                    <a href="{{ route('petty-cash.reconciliation', $fund) }}"
                                        class="text-xs font-medium text-themeBody hover:underline">Reconcile</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
