@extends('layouts.app')

@section('title', 'Agent Stock Requests')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-operations.index'),
            'label' => 'Back to Stock Operations',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Agent Stock Requests</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">
                    @if(auth()->user()->fieldAgentProfile && auth()->user()->branch_id)
                        View your allocations, request more stock from your branch, and track your requests.
                    @else
                        Review and approve, partially approve, or reject agent requests for your branch.
                    @endif
                </p>
            </div>
            @if(auth()->user()->fieldAgentProfile && auth()->user()->branch_id)
                <a href="{{ route('agent-stock-requests.create') }}"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Request stock from branch</span>
                </a>
            @endif
        </div>

        <!-- Stats (field agents see "my" stats; branch staff see only Incoming pending) -->
        @php
            $statCount = (($isFieldAgent ?? false) ? 3 : 0) + (($canReceiveRequests ?? false) ? 1 : 0);
            $statCols = $statCount ?: 1;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-{{ $statCols }} gap-4">
            @if($isFieldAgent ?? false)
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">My requests pending</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['my_pending'] ?? 0 }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">My requests approved</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['my_approved'] ?? 0 }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">My requests rejected</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['my_rejected'] ?? 0 }}</div>
            </div>
            @endif
            @if($canReceiveRequests ?? false)
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Incoming pending</div>
                <div class="text-2xl font-semibold text-sky-600 tracking-tight">{{ $stats['incoming_pending'] ?? 0 }}</div>
            </div>
            @endif
        </div>

        <!-- My allocations (field agents only) -->
        @if($myAllocations->isNotEmpty())
            <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder bg-themeInput/80">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">My allocations</h2>
                    <p class="text-sm text-themeMuted mt-0.5">Stock currently assigned to you by your branch</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-themeBorder">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-themeMuted uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-themeMuted uppercase tracking-wider">Branch</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-themeMuted uppercase tracking-wider">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-themeBorder">
                                @foreach($myAllocations as $alloc)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-themeHeading">{{ $alloc->product->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-themeBody">{{ $alloc->branch->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-themeHeading">{{ $alloc->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabs -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="border-b border-themeBorder bg-themeInput/80 px-6">
                <nav class="flex gap-6" aria-label="Tabs">
                    @if($isFieldAgent ?? false)
                    <a href="{{ route('agent-stock-requests.index', ['tab' => 'my-requests'] + request()->only('status')) }}"
                        class="py-4 px-1 border-b-2 font-medium text-sm inline-flex items-center gap-2 {{ ($tab ?? 'my-requests') === 'my-requests' ? 'border-primary text-primary' : 'border-transparent text-themeMuted hover:text-themeBody hover:border-themeBorder' }}">
                        My requests
                        @if(($stats['my_pending'] ?? 0) > 0)
                            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $stats['my_pending'] }}</span>
                        @endif
                    </a>
                    @endif
                    @if($canReceiveRequests ?? false)
                    <a href="{{ route('agent-stock-requests.index', ['tab' => 'incoming'] + request()->only('status')) }}"
                        class="py-4 px-1 border-b-2 font-medium text-sm inline-flex items-center gap-2 {{ ($tab ?? '') === 'incoming' ? 'border-primary text-primary' : 'border-transparent text-themeMuted hover:text-themeBody hover:border-themeBorder' }}">
                        Incoming requests
                        @if(($stats['incoming_pending'] ?? 0) > 0)
                            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">{{ $stats['incoming_pending'] }}</span>
                        @endif
                    </a>
                    @endif
                </nav>
            </div>

            <div class="p-6">
                @if(($canReceiveRequests ?? false) && ($tab ?? 'my-requests') === 'incoming')
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Requests from agents at your branch</h2>
                    @if($incomingRequests->count() > 0)
                        <div class="space-y-3">
                            @foreach($incomingRequests as $req)
                                <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 flex flex-wrap items-center justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-themeHeading">{{ $req->product->name ?? '—' }}</div>
                                        <div class="text-sm text-themeBody mt-1">
                                            <span class="font-medium">{{ $req->quantity_requested }}</span> units requested by
                                            <span class="font-medium">{{ $req->fieldAgent->name ?? 'Agent' }}</span>
                                            @if(($req->quantity_fulfilled ?? 0) > 0)
                                                · <span class="text-amber-700">{{ $req->quantity_fulfilled }} of {{ $req->quantity_requested }} fulfilled</span>
                                            @endif
                                            · {{ $req->created_at->format('M d, Y H:i') }}
                                        </div>
                                        @if($req->notes)
                                            <p class="text-sm text-themeMuted mt-1">{{ Str::limit($req->notes, 120) }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($req->canFulfillMore() && ($maxFulfillByRequest[$req->id] ?? 0) > 0)
                                            @php $maxFulfill = $maxFulfillByRequest[$req->id] ?? 0; @endphp
                                            <button type="button"
                                                onclick="document.getElementById('fulfill-modal-{{ $req->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center space-x-2 bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>{{ ($req->quantity_fulfilled ?? 0) > 0 ? 'Fulfill more' : 'Approve' }}</span>
                                            </button>
                                            <div id="fulfill-modal-{{ $req->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                                <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl">
                                                    <h3 class="text-lg font-semibold text-primary mb-4">{{ ($req->quantity_fulfilled ?? 0) > 0 ? 'Fulfill more' : 'Approve request' }}</h3>
                                                    <form method="POST" action="{{ route('agent-stock-requests.approve', $req) }}">
                                                        @csrf
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Quantity to give (max {{ $maxFulfill }})</label>
                                                        <input type="number" name="quantity_fulfilling" min="1" max="{{ $maxFulfill }}" value="{{ $maxFulfill }}"
                                                            class="w-full px-4 py-2.5 border border-themeBorder bg-themeInput text-themeBody rounded-xl mb-4">
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Notes (optional)</label>
                                                        <textarea name="fulfillment_notes" rows="3" class="w-full px-4 py-2.5 border border-themeBorder bg-themeInput text-themeBody rounded-xl mb-4" placeholder="e.g. Partial fulfillment; rest when replenished"></textarea>
                                                        <div class="flex gap-2">
                                                            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700">{{ ($req->quantity_fulfilled ?? 0) > 0 ? 'Fulfill' : 'Approve' }}</button>
                                                            <button type="button" onclick="document.getElementById('fulfill-modal-{{ $req->id }}').classList.add('hidden')" class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                        @if(($req->isPending() || $req->isPartiallyFulfilled()) && !$req->isClosed())
                                            <button type="button" onclick="document.getElementById('close-modal-{{ $req->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center space-x-2 bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading transition shadow-sm">
                                                <span>Close request</span>
                                            </button>
                                            <div id="close-modal-{{ $req->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                                <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl">
                                                    <h3 class="text-lg font-semibold text-primary mb-4">Close request</h3>
                                                    <p class="text-sm text-themeBody mb-4">No further units will be given. The agent will see the request as closed.</p>
                                                    <form method="POST" action="{{ route('agent-stock-requests.close', $req) }}">
                                                        @csrf
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                                        <textarea name="closed_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder bg-themeInput text-themeBody rounded-xl mb-4" placeholder="e.g. Awaiting restock"></textarea>
                                                        <div class="flex gap-2">
                                                            <button type="submit" class="bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading">Close request</button>
                                                            <button type="button" onclick="document.getElementById('close-modal-{{ $req->id }}').classList.add('hidden')" class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                        @if($req->isPending())
                                            <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center space-x-2 bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition shadow-sm">
                                                <span>Reject</span>
                                            </button>
                                            <div id="reject-modal-{{ $req->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                                <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl">
                                                    <h3 class="text-lg font-semibold text-primary mb-2">Reject request</h3>
                                                    <form method="POST" action="{{ route('agent-stock-requests.reject', $req) }}">
                                                        @csrf
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                                        <textarea name="rejection_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder bg-themeInput text-themeBody rounded-xl mb-4" placeholder="e.g. Insufficient branch stock"></textarea>
                                                        <div class="flex gap-2">
                                                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700">Reject</button>
                                                            <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').classList.add('hidden')" class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!$req->canFulfillMore() || (($maxFulfillByRequest[$req->id] ?? 0) < 1))
                                            @if($req->isClosed())
                                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">Closed ({{ $req->quantity_fulfilled }} of {{ $req->quantity_requested }} fulfilled)</span>
                                            @else
                                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $req->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($req->status === 'partially_fulfilled' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ $req->status === 'partially_fulfilled' ? 'Partially fulfilled' : ucfirst($req->status) }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $incomingRequests->withQueryString()->links('vendor.pagination.simple-tailwind') }}</div>
                    @else
                        <p class="text-themeMuted font-medium">No incoming agent requests.</p>
                    @endif
                @elseif($isFieldAgent ?? false)
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Your requests to your branch</h2>
                    @if($myRequests->count() > 0)
                        <div class="space-y-3">
                            @foreach($myRequests as $req)
                                <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-themeHeading">{{ $req->product->name ?? '—' }}</div>
                                        <div class="text-sm text-themeBody mt-1">
                                            <span class="font-medium">{{ $req->quantity_requested }}</span> units from <span class="font-medium">{{ $req->branch->name ?? 'Branch' }}</span>
                                            @if(($req->quantity_fulfilled ?? 0) > 0)
                                                · <span class="text-amber-700">{{ $req->quantity_fulfilled }} of {{ $req->quantity_requested }} fulfilled</span>
                                            @endif
                                            · {{ $req->created_at->format('M d, Y H:i') }}
                                        </div>
                                        @if($req->notes)
                                            <p class="text-sm text-themeMuted mt-1">{{ Str::limit($req->notes, 120) }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($req->isClosed())
                                            <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">Closed ({{ $req->quantity_fulfilled }} of {{ $req->quantity_requested }} fulfilled)</span>
                                        @elseif($req->status === 'pending')
                                            <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Pending</span>
                                        @elseif($req->status === 'partially_fulfilled')
                                            <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Partially fulfilled</span>
                                        @elseif($req->status === 'approved')
                                            <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">Approved</span>
                                        @else
                                            <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-red-100 text-red-800">Rejected</span>
                                            @if($req->rejection_reason)
                                                <span class="text-sm text-themeMuted" title="{{ $req->rejection_reason }}">{{ Str::limit($req->rejection_reason, 40) }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $myRequests->withQueryString()->links('vendor.pagination.simple-tailwind') }}</div>
                    @else
                        <p class="text-themeMuted font-medium">No requests yet. @if(auth()->user()->fieldAgentProfile && auth()->user()->branch_id)<a href="{{ route('agent-stock-requests.create') }}" class="text-primary hover:underline font-medium">Request stock from your branch</a> when you're running low.@endif</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

