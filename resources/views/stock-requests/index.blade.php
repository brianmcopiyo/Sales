@extends('layouts.app')

@section('title', 'Stock Requests')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-management.index'),
            'label' => 'Back to Stock Management',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Requests</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Request stock from other branches when you're running low
                </p>
            </div>
            <a href="{{ route('stock-requests.create') }}"
                class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Request stock</span>
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">My requests pending</div>
                <div class="text-2xl font-semibold text-amber-600 tracking-tight">{{ $stats['my_pending'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">My requests approved</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $stats['my_approved'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">My requests rejected</div>
                <div class="text-2xl font-semibold text-red-600 tracking-tight">{{ $stats['my_rejected'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Incoming pending</div>
                <div class="text-2xl font-semibold text-sky-600 tracking-tight">{{ $stats['incoming_pending'] }}</div>
            </div>
        </div>

        <!-- Tabs -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="border-b border-themeBorder bg-themeInput/80 px-6">
                <nav class="flex gap-6" aria-label="Tabs">
                    <a href="{{ route('stock-requests.index', ['tab' => 'my'] + request()->only('status')) }}"
                        class="py-4 px-1 border-b-2 font-medium text-sm inline-flex items-center gap-2 {{ ($tab ?? 'my') === 'my' ? 'border-primary text-primary' : 'border-transparent text-themeMuted hover:text-themeBody hover:border-themeBorder' }}">
                        My requests
                        @if (($stats['my_pending'] ?? 0) > 0)
                            <span
                                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $stats['my_pending'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('stock-requests.index', ['tab' => 'incoming'] + request()->only('status')) }}"
                        class="py-4 px-1 border-b-2 font-medium text-sm inline-flex items-center gap-2 {{ ($tab ?? '') === 'incoming' ? 'border-primary text-primary' : 'border-transparent text-themeMuted hover:text-themeBody hover:border-themeBorder' }}">
                        Incoming requests
                        @if (($stats['incoming_pending'] ?? 0) > 0)
                            <span
                                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">{{ $stats['incoming_pending'] }}</span>
                        @endif
                    </a>
                </nav>
            </div>

            <div class="p-6">
                @if ($tab === 'incoming')
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Requests from other branches (your
                        branch has stock)</h2>
                    @if ($incomingRequests->count() > 0)
                        <div class="space-y-3">
                            @foreach ($incomingRequests as $req)
                                <div
                                    class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 flex flex-wrap items-center justify-between gap-4">
                                    <a href="{{ route('stock-requests.show', $req) }}" class="flex-1 min-w-0 block hover:opacity-90 transition cursor-pointer">
                                        <div class="font-medium text-themeHeading">{{ $req->product->name }}</div>
                                        <div class="text-sm text-themeBody mt-1">
                                            <span class="font-medium">{{ $req->quantity_requested }}</span> units requested
                                            by
                                            <span class="font-medium">{{ $req->requestingBranch->name }}</span>
                                            @if (($req->quantity_fulfilled ?? 0) > 0)
                                                · <span class="text-amber-700">{{ $req->quantity_fulfilled }} of
                                                    {{ $req->quantity_requested }} fulfilled</span>
                                            @endif
                                            · {{ $req->created_at->format('M d, Y H:i') }}
                                        </div>
                                        @if ($req->notes)
                                            <p class="text-sm text-themeMuted mt-1">{{ Str::limit($req->notes, 120) }}</p>
                                        @endif
                                    </a>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if ($req->canFulfillMore() && ($maxFulfillByRequest[$req->id] ?? 0) > 0)
                                            @php $maxFulfill = $maxFulfillByRequest[$req->id] ?? 0; @endphp
                                            <button type="button"
                                                onclick="document.getElementById('fulfill-modal-{{ $req->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center space-x-2 bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>{{ ($req->quantity_fulfilled ?? 0) > 0 ? 'Fulfill more' : 'Approve' }}</span>
                                            </button>
                                            <div id="fulfill-modal-{{ $req->id }}"
                                                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('fulfill-modal-{{ $req->id }}').classList.add('hidden')">
                                                <div class="bg-themeCard rounded-2xl p-6 max-w-lg w-full shadow-xl max-h-[90vh] overflow-y-auto relative" onclick="event.stopPropagation()">
                                                    <button type="button" onclick="document.getElementById('fulfill-modal-{{ $req->id }}').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                    <h3 class="text-lg font-semibold text-primary mb-4 pr-8">
                                                        {{ ($req->quantity_fulfilled ?? 0) > 0 ? 'Fulfill more' : 'Approve request' }}
                                                    </h3>
                                                    <form method="POST"
                                                        action="{{ route('stock-requests.approve', $req) }}"
                                                        enctype="multipart/form-data">
                                                        @csrf
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Quantity
                                                            to send (max {{ $maxFulfill }})</label>
                                                        <input type="number" name="quantity_fulfilling" min="1"
                                                            max="{{ $maxFulfill }}" value="{{ $maxFulfill }}"
                                                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                            required>
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Reason
                                                            (optional, e.g. for partial fulfillment)</label>
                                                        <textarea name="fulfillment_reason" rows="2" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                            placeholder="e.g. Sending what we have in stock; rest when replenished"></textarea>
                                                        <div class="mb-4">
                                                            <label class="block text-sm font-medium text-themeBody mb-2">IMEIs (optional)</label>
                                                            <p class="text-xs text-themeMuted mb-1">Record which devices (by IMEI) you are sending. Devices must be at your branch for this product.</p>
                                                            <textarea name="imeis" rows="3" maxlength="2000" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-1"
                                                                placeholder="One IMEI per line or comma-separated"></textarea>
                                                            <label for="fulfill_imei_file_{{ $req->id }}" class="block text-xs font-medium text-themeBody mt-1">Or upload file (CSV/Excel)</label>
                                                            <input type="file" id="fulfill_imei_file_{{ $req->id }}" name="imei_file" accept=".csv,.xlsx,.xls"
                                                                class="w-full px-4 py-2 border border-themeBorder rounded-xl text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-themeHover file:text-themeBody mt-1">
                                                            <p class="mt-1 text-xs text-themeMuted"><a href="{{ asset('sample_imei_upload.csv') }}" download class="text-primary hover:underline font-medium">Download sample CSV</a> — one IMEI per row, header: <code class="text-themeBody">imei</code>.</p>
                                                            @error('imeis')
                                                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <button type="submit"
                                                                class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700">
                                                                {{ ($req->quantity_fulfilled ?? 0) > 0 ? 'Fulfill' : 'Approve' }}
                                                            </button>
                                                            <button type="button"
                                                                onclick="document.getElementById('fulfill-modal-{{ $req->id }}').classList.add('hidden')"
                                                                class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            @if (($req->isPending() || $req->isPartiallyFulfilled()) && !$req->isClosed())
                                                <button type="button"
                                                    onclick="document.getElementById('close-modal-{{ $req->id }}').classList.remove('hidden')"
                                                    class="inline-flex items-center space-x-2 bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading transition shadow-sm">
                                                    <span>Close request</span>
                                                </button>
                                                <div id="close-modal-{{ $req->id }}"
                                                    class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('close-modal-{{ $req->id }}').classList.add('hidden')">
                                                    <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl relative" onclick="event.stopPropagation()">
                                                        <button type="button" onclick="document.getElementById('close-modal-{{ $req->id }}').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                        <h3 class="text-lg font-semibold text-primary mb-4 pr-8">Close request
                                                        </h3>
                                                        <p class="text-sm text-themeBody mb-4">No further units will be sent.
                                                            The requesting branch will be notified.</p>
                                                        <form method="POST"
                                                            action="{{ route('stock-requests.close', $req) }}">
                                                            @csrf
                                                            <label
                                                                class="block text-sm font-medium text-themeBody mb-2">Reason
                                                                (optional)</label>
                                                            <textarea name="closed_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                                placeholder="e.g. Awaiting restock; will not fulfill remainder"></textarea>
                                                            <div class="flex gap-2">
                                                                <button type="submit"
                                                                    class="bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading">Close
                                                                    request</button>
                                                                <button type="button"
                                                                    onclick="document.getElementById('close-modal-{{ $req->id }}').classList.add('hidden')"
                                                                    class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($req->isPending())
                                                <button type="button"
                                                    onclick="document.getElementById('reject-form-{{ $req->id }}').classList.toggle('hidden')"
                                                    class="inline-flex items-center space-x-2 bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition shadow-sm">
                                                    <span>Reject</span>
                                                </button>
                                            @endif
                                            @if ($req->isPending())
                                                <div id="reject-form-{{ $req->id }}"
                                                    class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('reject-form-{{ $req->id }}').classList.add('hidden')">
                                                    <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl relative" onclick="event.stopPropagation()">
                                                        <button type="button" onclick="document.getElementById('reject-form-{{ $req->id }}').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                        <h3 class="text-lg font-semibold text-primary mb-2 pr-8">Reject request
                                                        </h3>
                                                        <form method="POST"
                                                            action="{{ route('stock-requests.reject', $req) }}">
                                                            @csrf
                                                            <label
                                                                class="block text-sm font-medium text-themeBody mb-2">Reason
                                                                (optional)</label>
                                                            <textarea name="rejection_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                                placeholder="e.g. insufficient stock"></textarea>
                                                            <div class="flex gap-2">
                                                                <button type="submit"
                                                                    class="bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700">Reject</button>
                                                                <button type="button"
                                                                    onclick="document.getElementById('reject-form-{{ $req->id }}').classList.add('hidden')"
                                                                    class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            @if ($req->isClosed())
                                                <span
                                                    class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">
                                                    Closed ({{ $req->quantity_fulfilled }} of
                                                    {{ $req->quantity_requested }} fulfilled)
                                                </span>
                                            @else
                                                <span
                                                    class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $req->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($req->status === 'partially_fulfilled' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ $req->status === 'partially_fulfilled' ? 'Partially fulfilled' : ucfirst($req->status) }}
                                                </span>
                                                @if ($req->isPartiallyFulfilled() && !$req->isClosed())
                                                    <button type="button"
                                                        onclick="document.getElementById('close-modal-else-{{ $req->id }}').classList.remove('hidden')"
                                                        class="inline-flex items-center space-x-2 bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading transition shadow-sm text-sm">
                                                        Close request
                                                    </button>
                                                    <div id="close-modal-else-{{ $req->id }}"
                                                        class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('close-modal-else-{{ $req->id }}').classList.add('hidden')">
                                                        <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl relative" onclick="event.stopPropagation()">
                                                            <button type="button" onclick="document.getElementById('close-modal-else-{{ $req->id }}').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                            <h3 class="text-lg font-semibold text-primary mb-4 pr-8">Close
                                                                request</h3>
                                                            <p class="text-sm text-themeBody mb-4">No further units will be
                                                                sent. The requesting branch will be notified.</p>
                                                            <form method="POST"
                                                                action="{{ route('stock-requests.close', $req) }}">
                                                                @csrf
                                                                <label
                                                                    class="block text-sm font-medium text-themeBody mb-2">Reason
                                                                    (optional)</label>
                                                                <textarea name="closed_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                                    placeholder="e.g. Awaiting restock; will not fulfill remainder"></textarea>
                                                                <div class="flex gap-2">
                                                                    <button type="submit"
                                                                        class="bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading">Close
                                                                        request</button>
                                                                    <button type="button"
                                                                        onclick="document.getElementById('close-modal-else-{{ $req->id }}').classList.add('hidden')"
                                                                        class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                            @if ($req->stockTransfers && $req->stockTransfers->isNotEmpty())
                                                @foreach ($req->stockTransfers as $tr)
                                                    <a href="{{ route('stock-transfers.show', $tr) }}"
                                                        class="text-sm font-medium text-primary hover:underline">View
                                                        transfer #{{ $tr->transfer_number }}</a>
                                                @endforeach
                                            @elseif ($req->stockTransfer)
                                                <a href="{{ route('stock-transfers.show', $req->stockTransfer) }}"
                                                    class="text-sm font-medium text-primary hover:underline">View
                                                    transfer</a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            {{ $incomingRequests->withQueryString()->links('vendor.pagination.simple-tailwind') }}</div>
                    @else
                        <p class="text-themeMuted font-medium">No incoming requests.</p>
                    @endif
                @else
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Requests your branch has made</h2>
                    @if ($myRequests->count() > 0)
                        <div class="space-y-3">
                            @foreach ($myRequests as $req)
                                <div
                                    class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 flex flex-wrap items-center justify-between gap-4">
                                    <a href="{{ route('stock-requests.show', $req) }}" class="block hover:opacity-90 transition cursor-pointer flex-1 min-w-0">
                                        <div class="font-medium text-themeHeading">{{ $req->product->name }}</div>
                                        <div class="text-sm text-themeBody mt-1">
                                            <span class="font-medium">{{ $req->quantity_requested }}</span> units from
                                            <span class="font-medium">{{ $req->requestedFromBranch->name }}</span>
                                            @if (($req->quantity_fulfilled ?? 0) > 0)
                                                · <span class="text-amber-700">{{ $req->quantity_fulfilled }} of
                                                    {{ $req->quantity_requested }} fulfilled</span>
                                            @endif
                                            · {{ $req->created_at->format('M d, Y H:i') }}
                                        </div>
                                        @if ($req->notes)
                                            <p class="text-sm text-themeMuted mt-1">{{ Str::limit($req->notes, 120) }}</p>
                                        @endif
                                    </a>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if ($req->isClosed())
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">
                                                Closed ({{ $req->quantity_fulfilled }} of {{ $req->quantity_requested }}
                                                fulfilled)
                                            </span>
                                            @if ($req->stockTransfers && $req->stockTransfers->isNotEmpty())
                                                @foreach ($req->stockTransfers as $tr)
                                                    <a href="{{ route('stock-transfers.show', $tr) }}"
                                                        class="text-sm font-medium text-primary hover:underline">View
                                                        transfer #{{ $tr->transfer_number }}</a>
                                                @endforeach
                                            @elseif ($req->stockTransfer)
                                                <a href="{{ route('stock-transfers.show', $req->stockTransfer) }}"
                                                    class="text-sm font-medium text-primary hover:underline">View
                                                    transfer</a>
                                            @endif
                                        @elseif ($req->status === 'pending')
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Pending</span>
                                        @elseif ($req->status === 'partially_fulfilled')
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-amber-100 text-amber-800">Partially
                                                fulfilled</span>
                                            @if ($req->stockTransfers && $req->stockTransfers->isNotEmpty())
                                                @foreach ($req->stockTransfers as $tr)
                                                    <a href="{{ route('stock-transfers.show', $tr) }}"
                                                        class="text-sm font-medium text-primary hover:underline">View
                                                        transfer #{{ $tr->transfer_number }}</a>
                                                @endforeach
                                            @endif
                                        @elseif ($req->status === 'approved')
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-emerald-100 text-emerald-800">Approved</span>
                                            @if ($req->stockTransfers && $req->stockTransfers->isNotEmpty())
                                                @foreach ($req->stockTransfers as $tr)
                                                    <a href="{{ route('stock-transfers.show', $tr) }}"
                                                        class="text-sm font-medium text-primary hover:underline">View
                                                        transfer #{{ $tr->transfer_number }}</a>
                                                @endforeach
                                            @elseif ($req->stockTransfer)
                                                <a href="{{ route('stock-transfers.show', $req->stockTransfer) }}"
                                                    class="text-sm font-medium text-primary hover:underline">View
                                                    transfer</a>
                                            @endif
                                        @else
                                            <span
                                                class="px-2.5 py-1 text-xs rounded-lg font-medium bg-red-100 text-red-800">Rejected</span>
                                            @if ($req->rejection_reason)
                                                <span class="text-sm text-themeMuted"
                                                    title="{{ $req->rejection_reason }}">{{ Str::limit($req->rejection_reason, 40) }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            {{ $myRequests->withQueryString()->links('vendor.pagination.simple-tailwind') }}</div>
                    @else
                        <p class="text-themeMuted font-medium">No requests yet. <a
                                href="{{ route('stock-requests.create') }}"
                                class="text-primary hover:underline font-medium">Request stock from another branch</a>
                            when you're running low.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

