@extends('layouts.app')

@section('title', 'Stock Request Details')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-requests.index', ['tab' => $isRequestingBranch ? 'my' : 'incoming']),
            'label' => 'Back to Stock Requests',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Request Details</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $stockRequest->product->name }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Request Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Product</div>
                            <div class="font-semibold text-themeHeading">{{ $stockRequest->product->name }}</div>
                            @if ($stockRequest->product->sku)
                                <div class="text-sm font-medium text-themeMuted">{{ $stockRequest->product->sku }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Quantity</div>
                            <div class="font-semibold text-primary">{{ $stockRequest->quantity_requested }} requested</div>
                            @if (($stockRequest->quantity_fulfilled ?? 0) > 0)
                                <div class="text-sm text-themeBody">{{ $stockRequest->quantity_fulfilled }} of {{ $stockRequest->quantity_requested }} fulfilled</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Requesting branch</div>
                            <div class="font-medium text-themeHeading">{{ $stockRequest->requestingBranch->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Requested from</div>
                            <div class="font-medium text-themeHeading">{{ $stockRequest->requestedFromBranch->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'partially_fulfilled' => 'bg-amber-100 text-amber-800',
                                    'approved' => 'bg-emerald-100 text-emerald-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            @if ($stockRequest->isClosed())
                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">
                                    Closed ({{ $stockRequest->quantity_fulfilled }} of {{ $stockRequest->quantity_requested }} fulfilled)
                                </span>
                            @else
                                <span class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $statusClasses[$stockRequest->status] ?? 'bg-themeHover text-themeHeading' }}">
                                    {{ $stockRequest->status === 'partially_fulfilled' ? 'Partially fulfilled' : ucfirst($stockRequest->status) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Created</div>
                            <div class="font-medium text-themeHeading">{{ $stockRequest->creator->name ?? '-' }}</div>
                            <div class="text-sm text-themeMuted">{{ $stockRequest->created_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>
                    @if ($stockRequest->notes)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm font-medium text-themeMuted mb-1">Notes</div>
                            <p class="text-themeBody whitespace-pre-wrap">{{ $stockRequest->notes }}</p>
                        </div>
                    @endif
                    @if ($stockRequest->rejection_reason)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm font-medium text-themeMuted mb-1">Rejection reason</div>
                            <p class="text-themeBody whitespace-pre-wrap">{{ $stockRequest->rejection_reason }}</p>
                            @if ($stockRequest->rejected_at)
                                <div class="text-sm text-themeMuted mt-1">{{ $stockRequest->rejected_at->format('M d, Y H:i') }}{{ $stockRequest->rejectedByUser ? ' · ' . $stockRequest->rejectedByUser->name : '' }}</div>
                            @endif
                        </div>
                    @endif
                    @if ($stockRequest->closed_reason)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm font-medium text-themeMuted mb-1">Close reason</div>
                            <p class="text-themeBody whitespace-pre-wrap">{{ $stockRequest->closed_reason }}</p>
                            @if ($stockRequest->closed_at)
                                <div class="text-sm text-themeMuted mt-1">{{ $stockRequest->closed_at->format('M d, Y H:i') }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                @if (($stockRequest->stockTransfers && $stockRequest->stockTransfers->isNotEmpty()) || $stockRequest->stockTransfer)
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Related transfers</h2>
                        <ul class="space-y-2">
                            @if ($stockRequest->stockTransfers && $stockRequest->stockTransfers->isNotEmpty())
                                @foreach ($stockRequest->stockTransfers as $tr)
                                    <li>
                                        <a href="{{ route('stock-transfers.show', $tr) }}" class="text-primary hover:underline font-medium">Transfer #{{ $tr->transfer_number }}</a>
                                        <span class="text-sm text-themeMuted"> — {{ $tr->quantity }} units · {{ $tr->created_at->format('M d, Y H:i') }}</span>
                                    </li>
                                @endforeach
                            @elseif ($stockRequest->stockTransfer)
                                <li>
                                    <a href="{{ route('stock-transfers.show', $stockRequest->stockTransfer) }}" class="text-primary hover:underline font-medium">Transfer #{{ $stockRequest->stockTransfer->transfer_number }}</a>
                                    <span class="text-sm text-themeMuted"> — {{ $stockRequest->stockTransfer->quantity }} units</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>

            @if ($isRequestedFromBranch)
                <div class="space-y-6">
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Actions</h2>
                        <div class="flex flex-wrap gap-2">
                            @if ($stockRequest->canFulfillMore() && ($maxFulfill ?? 0) > 0)
                                @php $maxFulfillVal = $maxFulfill ?? 0; @endphp
                                <button type="button"
                                    onclick="document.getElementById('fulfill-modal-show').classList.remove('hidden')"
                                    class="inline-flex items-center space-x-2 bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>{{ ($stockRequest->quantity_fulfilled ?? 0) > 0 ? 'Fulfill more' : 'Approve' }}</span>
                                </button>
                                <div id="fulfill-modal-show" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('fulfill-modal-show').classList.add('hidden')">
                                    <div class="bg-themeCard rounded-2xl p-6 max-w-lg w-full shadow-xl max-h-[90vh] overflow-y-auto relative" onclick="event.stopPropagation()">
                                        <button type="button" onclick="document.getElementById('fulfill-modal-show').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <h3 class="text-lg font-semibold text-primary mb-4 pr-8">
                                            {{ ($stockRequest->quantity_fulfilled ?? 0) > 0 ? 'Fulfill more' : 'Approve request' }}
                                        </h3>
                                        <form method="POST" action="{{ route('stock-requests.approve', $stockRequest) }}" enctype="multipart/form-data">
                                            @csrf
                                            <label class="block text-sm font-medium text-themeBody mb-2">Quantity to send (max {{ $maxFulfillVal }})</label>
                                            <input type="number" name="quantity_fulfilling" min="1" max="{{ $maxFulfillVal }}" value="{{ $maxFulfillVal }}"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4" required>
                                            <label class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                            <textarea name="fulfillment_reason" rows="2" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                placeholder="e.g. Sending what we have in stock"></textarea>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-themeBody mb-2">IMEIs (optional)</label>
                                                <p class="text-xs text-themeMuted mb-1">Record which devices (by IMEI) you are sending. Devices must be at your branch for this product. They will move to the requesting branch when they receive the transfer.</p>
                                                <textarea name="imeis" rows="3" maxlength="2000" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-1"
                                                    placeholder="One IMEI per line or comma-separated">{{ old('imeis') }}</textarea>
                                                <label for="fulfill_imei_file" class="block text-xs font-medium text-themeBody mt-1">Or upload file (CSV/Excel)</label>
                                                <input type="file" id="fulfill_imei_file" name="imei_file" accept=".csv,.xlsx,.xls"
                                                    class="w-full px-4 py-2 border border-themeBorder rounded-xl text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-themeHover file:text-themeBody mt-1">
                                                <p class="mt-1 text-xs text-themeMuted"><a href="{{ asset('sample_imei_upload.csv') }}" download class="text-primary hover:underline font-medium">Download sample CSV</a> — one IMEI per row, header: <code class="text-themeBody">imei</code>.</p>
                                                @error('imeis')
                                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700">
                                                    {{ ($stockRequest->quantity_fulfilled ?? 0) > 0 ? 'Fulfill' : 'Approve' }}
                                                </button>
                                                <button type="button" onclick="document.getElementById('fulfill-modal-show').classList.add('hidden')"
                                                    class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @if (($stockRequest->isPending() || $stockRequest->isPartiallyFulfilled()) && !$stockRequest->isClosed())
                                    <button type="button" onclick="document.getElementById('close-modal-show').classList.remove('hidden')"
                                        class="inline-flex items-center space-x-2 bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading transition shadow-sm">
                                        <span>Close request</span>
                                    </button>
                                    <div id="close-modal-show" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('close-modal-show').classList.add('hidden')">
                                        <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl relative" onclick="event.stopPropagation()">
                                            <button type="button" onclick="document.getElementById('close-modal-show').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                            <h3 class="text-lg font-semibold text-primary mb-4 pr-8">Close request</h3>
                                            <p class="text-sm text-themeBody mb-4">No further units will be sent. The requesting branch will be notified.</p>
                                            <form method="POST" action="{{ route('stock-requests.close', $stockRequest) }}">
                                                @csrf
                                                <label class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                                <textarea name="closed_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                    placeholder="e.g. Awaiting restock"></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading">Close request</button>
                                                    <button type="button" onclick="document.getElementById('close-modal-show').classList.add('hidden')"
                                                        class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                                @if ($stockRequest->isPending())
                                    <button type="button" onclick="document.getElementById('reject-form-show').classList.toggle('hidden')"
                                        class="inline-flex items-center space-x-2 bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition shadow-sm">
                                        <span>Reject</span>
                                    </button>
                                    <div id="reject-form-show" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" onclick="if (event.target === this) document.getElementById('reject-form-show').classList.add('hidden')">
                                        <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl relative" onclick="event.stopPropagation()">
                                            <button type="button" onclick="document.getElementById('reject-form-show').classList.add('hidden')" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                            <h3 class="text-lg font-semibold text-primary mb-2 pr-8">Reject request</h3>
                                            <form method="POST" action="{{ route('stock-requests.reject', $stockRequest) }}">
                                                @csrf
                                                <label class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                                <textarea name="rejection_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                    placeholder="e.g. insufficient stock"></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700">Reject</button>
                                                    <button type="button" onclick="document.getElementById('reject-form-show').classList.add('hidden')"
                                                        class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @else
                                {{-- Can't fulfill (e.g. no stock) but can still Reject or Close --}}
                                @if ($stockRequest->isClosed())
                                    <span class="px-2.5 py-1 text-xs rounded-lg font-medium bg-themeHover text-themeHeading">
                                        Closed ({{ $stockRequest->quantity_fulfilled }} of {{ $stockRequest->quantity_requested }} fulfilled)
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs rounded-lg font-medium {{ $stockRequest->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($stockRequest->status === 'partially_fulfilled' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $stockRequest->status === 'partially_fulfilled' ? 'Partially fulfilled' : ucfirst($stockRequest->status) }}
                                    </span>
                                    @if (($maxFulfill ?? 0) < 1)
                                        <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-200">
                                            <p class="text-sm font-medium text-amber-900">
                                                Insufficient stock to fulfill this request.
                                                @if ($availableQuantity !== null)
                                                    You have <strong>{{ $availableQuantity }}</strong> {{ Str::plural('unit', $availableQuantity) }} available; <strong>{{ $stockRequest->remainderQuantity() }}</strong> {{ Str::plural('unit', $stockRequest->remainderQuantity()) }} still requested.
                                                @else
                                                    The requesting branch is waiting for a response.
                                                @endif
                                            </p>
                                            <p class="text-sm text-amber-800 mt-1">Please <strong>Close request</strong> (e.g. “Awaiting restock – will notify when available”) or <strong>Reject</strong> (e.g. “Insufficient stock”) so they are notified and can plan accordingly.</p>
                                        </div>
                                    @endif
                                    @if (($stockRequest->isPending() || $stockRequest->isPartiallyFulfilled()) && !$stockRequest->isClosed())
                                        <button type="button" onclick="document.getElementById('close-modal-show').classList.remove('hidden')"
                                            class="inline-flex items-center space-x-2 bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading transition shadow-sm">
                                            <span>Close request</span>
                                        </button>
                                        <div id="close-modal-show" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                            <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl">
                                                <h3 class="text-lg font-semibold text-primary mb-4">Close request</h3>
                                                <p class="text-sm text-themeBody mb-4">No further units will be sent. The requesting branch will be notified so they are not left waiting. Adding a reason (e.g. awaiting restock, insufficient stock) helps them plan.</p>
                                                <form method="POST" action="{{ route('stock-requests.close', $stockRequest) }}">
                                                    @csrf
                                                    <label class="block text-sm font-medium text-themeBody mb-2">Reason (recommended so requestor knows why)</label>
                                                    <textarea name="closed_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                        placeholder="e.g. Awaiting restock – will notify when available. Or: Insufficient stock at the moment."></textarea>
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="bg-themeBody text-themeCard px-4 py-2 rounded-xl font-medium hover:bg-themeHeading">Close request</button>
                                                        <button type="button" onclick="document.getElementById('close-modal-show').classList.add('hidden')"
                                                            class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        @if ($stockRequest->isPending())
                                            <button type="button" onclick="document.getElementById('reject-form-show').classList.toggle('hidden')"
                                                class="inline-flex items-center space-x-2 bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition shadow-sm">
                                                <span>Reject</span>
                                            </button>
                                            <div id="reject-form-show" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                                <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl">
                                                    <h3 class="text-lg font-semibold text-primary mb-2">Reject request</h3>
                                                    <p class="text-sm text-themeBody mb-3">The requesting branch will be notified so they are not left waiting. Adding a reason (e.g. insufficient stock) helps them plan.</p>
                                                    <form method="POST" action="{{ route('stock-requests.reject', $stockRequest) }}">
                                                        @csrf
                                                        <label class="block text-sm font-medium text-themeBody mb-2">Reason (recommended)</label>
                                                        <textarea name="rejection_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4"
                                                            placeholder="e.g. Insufficient stock at the moment. Or: Product not available at this branch."></textarea>
                                                        <div class="flex gap-2">
                                                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700">Reject</button>
                                                            <button type="button" onclick="document.getElementById('reject-form-show').classList.add('hidden')"
                                                                class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
