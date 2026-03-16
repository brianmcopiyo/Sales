@extends('layouts.app')

@section('title', 'Stock Transfer Details')

@section('content')
    <div class="w-full space-y-6" x-data="{ partialModalOpen: false, rejectModalOpen: false, receiveModalOpen: false, attachImeiModalOpen: false, previewOpen: false, previewSrc: '', previewName: '', previewDownloadUrl: '' }">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Transfer Details</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $stockTransfer->transfer_number ?? 'Transfer' }}</p>
            </div>
            <a href="{{ route('stock-transfers.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Transfer Information Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Transfer Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if ($stockTransfer->items && $stockTransfer->items->count() > 1)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-2">Items</div>
                                <ul class="space-y-2">
                                    @foreach ($stockTransfer->items as $item)
                                        <li class="flex items-center justify-between py-2 border-b border-themeBorder last:border-0">
                                            <span class="font-medium text-themeHeading">{{ $item->product->name ?? $item->product_id }}</span>
                                            <span class="text-primary font-semibold">
                                                {{ $item->quantity }}
                                                @if (in_array($stockTransfer->status, ['received', 'pending_sender_confirmation']) && $item->quantity_received !== null)
                                                    <span class="text-themeBody font-normal">(received: {{ $item->quantity_received }})</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="mt-2 text-sm font-medium text-themeMuted">Total: {{ $stockTransfer->total_quantity }} units</div>
                            </div>
                        @else
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Product</div>
                                <div class="text-lg font-semibold text-themeHeading">{{ $stockTransfer->product?->name ?? $stockTransfer->items->first()?->product?->name ?? '-' }}</div>
                                <div class="text-sm font-medium text-themeMuted">{{ $stockTransfer->product?->sku ?? $stockTransfer->items->first()?->product?->sku ?? '' }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Quantity</div>
                                <div class="text-lg font-semibold text-primary">
                                    {{ $stockTransfer->total_quantity }}
                                    @if (in_array($stockTransfer->status, ['received', 'pending_sender_confirmation']) && $stockTransfer->quantity_received !== null)
                                        <span class="text-sm font-medium text-themeBody">
                                            (received: {{ $stockTransfer->quantity_received }} of {{ $stockTransfer->total_quantity }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">From Branch</div>
                            <div class="text-lg font-medium text-themeHeading">{{ $stockTransfer->fromBranch->name }}</div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">To Branch</div>
                            <div class="text-lg font-medium text-themeHeading">{{ $stockTransfer->toBranch->name }}</div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'in_transit' => 'bg-sky-100 text-sky-800',
                                    'pending_sender_confirmation' => 'bg-orange-100 text-orange-800',
                                    'received' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-themeHover text-themeHeading',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'returned' => 'bg-rose-100 text-rose-800',
                                ];
                            @endphp
                            <span
                                class="px-3 py-1.5 text-sm rounded-lg font-medium {{ $statusColors[$stockTransfer->status] ?? 'bg-themeHover text-themeHeading' }}">
                                {{ ucfirst(str_replace('_', ' ', $stockTransfer->status)) }}
                            </span>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Transfer Number</div>
                            <div class="font-medium text-themeHeading">{{ $stockTransfer->transfer_number ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- People & Dates Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">People & Dates</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Created By</div>
                            <div class="font-medium text-themeHeading">{{ $stockTransfer->creator->name ?? '-' }}</div>
                            <div class="text-sm font-medium text-themeMuted">
                                {{ $stockTransfer->created_at->format('M d, Y H:i') }}</div>
                        </div>

                        @if ($stockTransfer->received_by)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Received By</div>
                                <div class="font-medium text-themeHeading">{{ $stockTransfer->receiver->name ?? '-' }}
                                </div>
                                <div class="text-sm font-medium text-themeMuted">
                                    {{ $stockTransfer->received_at ? $stockTransfer->received_at->format('M d, Y H:i') : '-' }}
                                </div>
                                @if ($stockTransfer->received_notes)
                                    <div class="text-sm font-medium text-themeBody mt-1 whitespace-pre-wrap">
                                        {{ $stockTransfer->received_notes }}</div>
                                @endif
                                @if ($stockTransfer->receptionAttachments && $stockTransfer->receptionAttachments->isNotEmpty())
                                    <div class="mt-2">
                                        <div class="text-sm font-medium text-themeMuted mb-2">Reception evidence</div>
                                        <ul class="flex flex-wrap gap-3">
                                            @foreach ($stockTransfer->receptionAttachments as $att)
                                                @php $previewUrl = $att->isImage() ? asset('storage/' . $att->file_path) : null; @endphp
                                                <li class="flex flex-col items-start">
                                                    @if ($att->isImage())
                                                        <button type="button"
                                                            @click="previewOpen = true; previewSrc = '{{ $previewUrl }}'; previewName = '{{ addslashes($att->file_name) }}'; previewDownloadUrl = '{{ route('stock-transfers.reception-attachment.download', $att) }}'"
                                                            class="block rounded-lg border border-themeBorder overflow-hidden hover:ring-2 hover:ring-[#006F78]/30 focus:outline-none focus:ring-2 focus:ring-primary/40 transition">
                                                            <img src="{{ $previewUrl }}" alt="{{ $att->file_name }}"
                                                                class="h-20 w-20 object-cover bg-themeInput">
                                                        </button>
                                                        <a href="{{ route('stock-transfers.reception-attachment.download', $att) }}"
                                                            class="mt-1 text-xs font-medium text-primary hover:underline truncate max-w-[120px]"
                                                            title="{{ $att->file_name }}">
                                                            {{ $att->file_name }}
                                                        </a>
                                                    @else
                                                        <a href="{{ route('stock-transfers.reception-attachment.download', $att) }}"
                                                            class="text-sm font-medium text-primary hover:underline inline-flex items-center gap-1">
                                                            <svg class="w-4 h-4 shrink-0" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            {{ $att->file_name }}
                                                        </a>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    {{-- Image preview modal --}}
                                    <div x-show="previewOpen" x-cloak
                                        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                                        aria-modal="true">
                                        <div x-show="previewOpen" x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                            class="absolute inset-0 bg-black/70" @click="previewOpen = false"></div>
                                        <div x-show="previewOpen" x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            class="relative max-w-4xl max-h-[90vh] w-full flex flex-col items-center">
                                            <img :src="previewSrc" :alt="previewName"
                                                class="max-h-[85vh] w-auto object-contain rounded-lg shadow-2xl"
                                                @click.stop>
                                            <div class="mt-3 flex items-center gap-3">
                                                <a :href="previewSrc" target="_blank" rel="noopener"
                                                    class="bg-themeCard/90 text-themeHeading px-4 py-2 rounded-xl font-medium hover:bg-themeCard transition text-sm">Open
                                                    in new tab</a>
                                                <a :href="previewDownloadUrl"
                                                    class="bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition text-sm">Download</a>
                                                <button type="button" @click="previewOpen = false"
                                                    class="bg-themeHover text-themeHeading px-4 py-2 rounded-xl font-medium hover:bg-themeBorder transition text-sm">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($stockTransfer->status === 'pending_sender_confirmation')
                                    <div class="text-xs font-medium text-orange-600 mt-1">Awaiting sender confirmation</div>
                                @endif
                            </div>
                        @endif

                        @if ($stockTransfer->sender_confirmed_by)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Partial reception confirmed by</div>
                                <div class="font-medium text-themeHeading">
                                    {{ $stockTransfer->senderConfirmedBy->name ?? '-' }}
                                </div>
                                <div class="text-sm font-medium text-themeMuted">
                                    {{ $stockTransfer->sender_confirmed_at ? $stockTransfer->sender_confirmed_at->format('M d, Y H:i') : '-' }}
                                </div>
                            </div>
                        @endif

                        @if ($stockTransfer->transferred_at)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Transferred At</div>
                                <div class="font-medium text-themeHeading">
                                    {{ $stockTransfer->transferred_at->format('M d, Y H:i') }}</div>
                            </div>
                        @endif
                        @if ($stockTransfer->status === 'rejected' && $stockTransfer->rejection_reason)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Rejection Reason</div>
                                <div class="font-medium text-themeHeading whitespace-pre-wrap">
                                    {{ $stockTransfer->rejection_reason }}</div>
                                @if ($stockTransfer->rejectedByUser)
                                    <div class="flex items-center gap-2 mt-2">
                                        <x-profile-picture :user="$stockTransfer->rejectedByUser" size="xs" />
                                        <div class="text-xs font-medium text-themeMuted">Rejected by
                                            {{ $stockTransfer->rejectedByUser->name }} ·
                                            {{ $stockTransfer->rejected_at?->format('M d, Y H:i') }}</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if ($stockTransfer->notes)
                    <!-- Notes Card -->
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Notes</h2>
                        <div class="font-medium text-themeHeading whitespace-pre-wrap">{{ $stockTransfer->notes }}</div>
                    </div>
                @endif

                @if ($stockTransfer->transferDevices && $stockTransfer->transferDevices->isNotEmpty())
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Devices transferred</h2>
                        <p class="text-sm text-themeMuted mb-3">IMEIs recorded for this transfer. Devices have been moved from {{ $stockTransfer->fromBranch->name }} to {{ $stockTransfer->toBranch->name }}.</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-themeBorder">
                                        <th class="text-left py-2 font-medium text-themeMuted">IMEI</th>
                                        <th class="text-left py-2 font-medium text-themeMuted">Current branch</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockTransfer->transferDevices as $device)
                                        <tr class="border-b border-themeBorder/50">
                                            <td class="py-2 font-medium text-themeHeading">{{ $device->imei }}</td>
                                            <td class="py-2 text-themeBody">{{ $device->branch->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Actions Card -->
                @if (in_array($stockTransfer->status, ['pending', 'in_transit']))
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Actions</h2>
                        <div class="flex flex-wrap gap-3">
                            @if (auth()->user()->branch_id == $stockTransfer->to_branch_id &&
                                    auth()->user()?->hasPermission('stock-transfers.receive'))
                                {{-- 1. Receive (full, opens modal with optional IMEI upload) --}}
                                <button type="button" @click="receiveModalOpen = true"
                                    class="inline-flex items-center space-x-2 bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Receive ({{ $stockTransfer->total_quantity }} units)</span>
                                </button>
                                {{-- 2. Partially approve (opens modal) --}}
                                <button type="button" @click="partialModalOpen = true"
                                    class="inline-flex items-center space-x-2 bg-amber-500 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-amber-600 transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Partially approve</span>
                                </button>
                                {{-- 3. Reject (opens modal) --}}
                                @if (auth()->user()?->hasPermission('stock-transfers.reject'))
                                    <button type="button" @click="rejectModalOpen = true"
                                        class="inline-flex items-center space-x-2 bg-red-100 text-red-800 px-6 py-2.5 rounded-xl font-medium hover:bg-red-200 transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span>Reject transfer</span>
                                    </button>
                                @endif
                            @endif

                            @if (auth()->user()->branch_id == $stockTransfer->from_branch_id)
                                <button type="button" @click="attachImeiModalOpen = true"
                                    class="inline-flex items-center space-x-2 bg-sky-500 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-sky-600 transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Attach IMEIs</span>
                                </button>
                                <form method="POST" action="{{ route('stock-transfers.cancel', $stockTransfer) }}"
                                    class="inline"
                                    onsubmit="return confirm('Are you sure you want to cancel this transfer?')">
                                    @csrf
                                    <button type="submit"
                                        class="bg-amber-500 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-amber-600 transition shadow-sm flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span>Cancel Transfer</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                @if (auth()->user()->branch_id == $stockTransfer->to_branch_id &&
                        auth()->user()?->hasPermission('stock-transfers.receive') &&
                        in_array($stockTransfer->status, ['pending', 'in_transit']))
                    {{-- Full receive modal (with optional IMEI upload) --}}
                    <div x-show="receiveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div x-show="receiveModalOpen" x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                class="fixed inset-0 bg-themeInput/75" @click="receiveModalOpen = false"></div>
                            <div x-show="receiveModalOpen" x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="relative bg-themeCard rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                                <button type="button" @click="receiveModalOpen = false" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <h3 class="text-lg font-semibold text-primary mb-2 pr-8">Receive transfer</h3>
                                <p class="text-sm text-themeBody mb-4">Record full quantity received. Devices attached by the sender will be moved to your branch.</p>
                                <form method="POST" action="{{ route('stock-transfers.receive', $stockTransfer) }}"
                                    enctype="multipart/form-data"
                                    onsubmit="return confirm('Receive {{ $stockTransfer->total_quantity }} units?');">
                                    @csrf
                                    @if ($stockTransfer->items && $stockTransfer->items->count() > 1)
                                        @foreach ($stockTransfer->items as $item)
                                            <input type="hidden" name="items[{{ $item->id }}][quantity_received]" value="{{ $item->quantity }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="quantity_received" value="{{ $stockTransfer->total_quantity }}">
                                    @endif
                                    <div class="space-y-4">
                                        <div>
                                            <label for="receive_notes" class="block text-sm font-medium text-themeBody mb-1">Notes (optional)</label>
                                            <textarea id="receive_notes" name="received_notes" rows="2" maxlength="2000"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                placeholder="e.g. All units in good condition">{{ old('received_notes') }}</textarea>
                                        </div>
                                        <div>
                                            <label for="receive_attachments" class="block text-sm font-medium text-themeBody mb-1">Evidence (optional)</label>
                                            <input type="file" id="receive_attachments" name="attachments[]" multiple
                                                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-themeHover file:text-themeBody">
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="submit"
                                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark">Receive</button>
                                            <button type="button" @click="receiveModalOpen = false"
                                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    {{-- Partial approval modal --}}
                    <div x-show="partialModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div x-show="partialModalOpen" x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                class="fixed inset-0 bg-themeInput0/75" @click="partialModalOpen = false"></div>
                            <div x-show="partialModalOpen" x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="relative bg-themeCard rounded-2xl shadow-xl max-w-md w-full p-6">
                                <button type="button" @click="partialModalOpen = false" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <h3 class="text-lg font-semibold text-primary mb-2 pr-8">Partially approve</h3>
                                <p class="text-sm text-themeBody mb-4">Record quantity actually received (max
                                    {{ $stockTransfer->total_quantity }}). Sender must confirm before stock is credited.</p>
                                <form method="POST" action="{{ route('stock-transfers.receive', $stockTransfer) }}"
                                    enctype="multipart/form-data"
                                    onsubmit="return confirmPartialReception(this, {{ $stockTransfer->total_quantity }});">
                                    @csrf
                                    <div class="space-y-4">
                                        @if ($stockTransfer->items && $stockTransfer->items->count() > 1)
                                            @foreach ($stockTransfer->items as $item)
                                                <div>
                                                    <label for="quantity_received_{{ $item->id }}"
                                                        class="block text-sm font-medium text-themeBody mb-1">{{ $item->product->name ?? 'Item' }} (max {{ $item->quantity }}) *</label>
                                                    <input type="number" id="quantity_received_{{ $item->id }}"
                                                        name="items[{{ $item->id }}][quantity_received]"
                                                        required min="0" max="{{ $item->quantity }}"
                                                        value="{{ old("items.{$item->id}.quantity_received", $item->quantity) }}"
                                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                                    @error("items.{$item->id}.quantity_received")
                                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @endforeach
                                        @else
                                            <div>
                                                <label for="quantity_received"
                                                    class="block text-sm font-medium text-themeBody mb-1">Quantity received *</label>
                                                <input type="number" id="quantity_received" name="quantity_received"
                                                    required min="1" max="{{ $stockTransfer->total_quantity }}"
                                                    value="{{ old('quantity_received', $stockTransfer->total_quantity - 1) }}"
                                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                                <p class="text-xs text-themeMuted mt-1">Max: {{ $stockTransfer->total_quantity }}</p>
                                                @error('quantity_received')
                                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                        <div>
                                            <label for="received_notes"
                                                class="block text-sm font-medium text-themeBody mb-1">Notes
                                                (optional)</label>
                                            <textarea id="received_notes" name="received_notes" rows="2" maxlength="2000"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                placeholder="e.g. 2 damaged, 1 missing">{{ old('received_notes') }}</textarea>
                                            @error('received_notes')
                                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="attachments"
                                                class="block text-sm font-medium text-themeBody mb-1">Evidence
                                                (optional)</label>
                                            <input type="file" id="attachments" name="attachments[]" multiple
                                                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,image/jpeg,image/png,image/gif,image/webp,application/pdf"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-themeHover file:text-themeBody hover:file:bg-themeBorder">
                                            <p class="text-xs text-themeMuted mt-1">Photos or PDFs (e.g. damaged items,
                                                proof
                                                of receipt). Max 5MB per file. JPG, PNG, GIF, WebP, PDF.</p>
                                            @error('attachments.*')
                                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="submit"
                                                class="flex-1 bg-amber-500 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-amber-600">Submit</button>
                                            <button type="button" @click="partialModalOpen = false"
                                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    {{-- Reject modal --}}
                    @if (auth()->user()?->hasPermission('stock-transfers.reject'))
                        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                            aria-modal="true">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div x-show="rejectModalOpen" x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    class="fixed inset-0 bg-themeInput0/75" @click="rejectModalOpen = false"></div>
                                <div x-show="rejectModalOpen" x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="relative bg-themeCard rounded-2xl shadow-xl max-w-md w-full p-6">
                                    <button type="button" @click="rejectModalOpen = false" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <h3 class="text-lg font-semibold text-primary mb-2 pr-8">Reject transfer</h3>
                                    <p class="text-sm text-themeBody mb-4">Provide a reason. Stock will be returned to the
                                        sender branch.</p>
                                    <form method="POST" action="{{ route('stock-transfers.reject', $stockTransfer) }}"
                                        onsubmit="return confirm('Reject this transfer? Stock will be returned to the sender.');">
                                        @csrf
                                        <div class="space-y-4">
                                            <div>
                                                <label for="rejection_reason"
                                                    class="block text-sm font-medium text-themeBody mb-1">Rejection reason
                                                    *</label>
                                                <textarea id="rejection_reason" name="rejection_reason" required rows="3" maxlength="2000"
                                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                    placeholder="Enter reason for rejecting...">{{ old('rejection_reason') }}</textarea>
                                                @error('rejection_reason')
                                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="flex gap-3">
                                                <button type="submit"
                                                    class="flex-1 bg-red-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-red-700">Reject</button>
                                                <button type="button" @click="rejectModalOpen = false"
                                                    class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Sender: Attach IMEIs (devices being sent) --}}
                @if (auth()->user()->branch_id == $stockTransfer->from_branch_id &&
                        in_array($stockTransfer->status, ['pending', 'in_transit']))
                    <div x-show="attachImeiModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div x-show="attachImeiModalOpen" x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                class="fixed inset-0 bg-themeInput/75" @click="attachImeiModalOpen = false"></div>
                            <div x-show="attachImeiModalOpen" x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="relative bg-themeCard rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                                <button type="button" @click="attachImeiModalOpen = false" class="absolute top-4 right-4 p-1 rounded-lg text-themeMuted hover:bg-themeHover hover:text-themeBody transition" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <h3 class="text-lg font-semibold text-primary mb-2 pr-8">Attach IMEIs (devices being sent)</h3>
                                <p class="text-sm text-themeBody mb-4">Record which devices (by IMEI) you are sending. Only devices at your branch ({{ $stockTransfer->fromBranch->name }}) for the selected product can be attached. They will be moved to {{ $stockTransfer->toBranch->name }} when the transfer is received.</p>
                                <form method="POST" action="{{ route('stock-transfers.attach-devices', $stockTransfer) }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="space-y-4">
                                        @if ($stockTransfer->items && $stockTransfer->items->count() > 1)
                                            <div>
                                                <label for="attach_product_id" class="block text-sm font-medium text-themeBody mb-1">Product *</label>
                                                <select id="attach_product_id" name="product_id" required
                                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                                    @foreach ($stockTransfer->items as $item)
                                                        @php
                                                            $alreadyAttached = $stockTransfer->transferDevices->where('product_id', $item->product_id)->count();
                                                            $maxNew = max(0, $item->quantity - $alreadyAttached);
                                                        @endphp
                                                        <option value="{{ $item->product_id }}" {{ old('product_id') == $item->product_id ? 'selected' : '' }}>
                                                            {{ $item->product->name ?? $item->product_id }} (max {{ $maxNew }} device(s) to attach)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        <div>
                                            <label for="attach_imeis" class="block text-sm font-medium text-themeBody mb-1">IMEIs *</label>
                                            <textarea id="attach_imeis" name="imeis" rows="4" maxlength="2000"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                placeholder="One IMEI per line or comma-separated">{{ old('imeis') }}</textarea>
                                            <label for="attach_imei_file" class="block text-xs font-medium text-themeBody mt-1">Or upload file (CSV/Excel)</label>
                                            <input type="file" id="attach_imei_file" name="imei_file" accept=".csv,.xlsx,.xls"
                                                class="w-full px-4 py-2 border border-themeBorder rounded-xl text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-themeHover file:text-themeBody mt-1">
                                            <p class="mt-1 text-xs text-themeMuted"><a href="{{ asset('sample_imei_upload.csv') }}" download class="text-primary hover:underline font-medium">Download sample CSV</a> — one IMEI per row, header: <code class="text-themeBody">imei</code>.</p>
                                            <p class="text-xs text-themeMuted mt-1">Devices must already exist at your branch for the selected product.</p>
                                            @error('imeis')
                                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="submit"
                                                class="flex-1 bg-sky-500 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-sky-600">Attach devices</button>
                                            <button type="button" @click="attachImeiModalOpen = false"
                                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Sender: Confirm partial reception (when status is pending_sender_confirmation) -->
                @if (
                    $stockTransfer->status === 'pending_sender_confirmation' &&
                        auth()->user()->branch_id == $stockTransfer->from_branch_id)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Confirm partial reception</h2>
                        <p class="text-sm font-medium text-themeBody mb-4">The recipient recorded
                            {{ $stockTransfer->quantity_received }} of {{ $stockTransfer->total_quantity }} units received.
                            Confirm to credit {{ $stockTransfer->quantity_received }} units to the recipient branch.</p>
                        <div class="flex flex-wrap gap-3">
                            <form method="POST"
                                action="{{ route('stock-transfers.confirm-partial-reception', $stockTransfer) }}"
                                class="inline"
                                onsubmit="return confirm('Confirm partial reception? {{ $stockTransfer->quantity_received }} units will be credited to the recipient branch.');">
                                @csrf
                                <button type="submit"
                                    class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Confirm partial reception</span>
                                </button>
                            </form>
                            <a href="{{ route('stock-transfers.show', $stockTransfer) }}#return-form"
                                class="bg-red-100 text-red-800 px-6 py-2.5 rounded-xl font-medium hover:bg-red-200 transition shadow-sm flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Return / disagree</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Links Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        @php $productForLink = $stockTransfer->product ?? $stockTransfer->items->first()?->product; @endphp
                        @if($productForLink)
                        <a href="{{ route('products.show', $productForLink) }}"
                            class="block w-full bg-themeInput/80 text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeInput transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>View Product</span>
                        </a>
                        @endif
                        <a href="{{ route('branches.show', $stockTransfer->fromBranch) }}"
                            class="block w-full bg-themeInput/80 text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeInput transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            <span>View From Branch</span>
                        </a>
                        <a href="{{ route('branches.show', $stockTransfer->toBranch) }}"
                            class="block w-full bg-themeInput/80 text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeInput transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            <span>View To Branch</span>
                        </a>
                        <a href="{{ route('stock-transfers.index') }}?product={{ $stockTransfer->product_id }}"
                            class="block w-full bg-themeInput/80 text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeInput transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <span>View Product Transfers</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Product Transfers</div>
                            <div class="text-2xl font-semibold text-primary tracking-tight">
                                {{ \App\Models\StockTransfer::where('product_id', $stockTransfer->product_id)->count() }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branch Transfers</div>
                            <div class="text-2xl font-semibold text-primary tracking-tight">
                                {{ \App\Models\StockTransfer::where(function ($q) use ($stockTransfer) {
                                    $q->where('from_branch_id', $stockTransfer->from_branch_id)->orWhere('to_branch_id', $stockTransfer->to_branch_id);
                                })->count() }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Pending Transfers</div>
                            <div class="text-2xl font-semibold text-amber-600 tracking-tight">
                                {{ \App\Models\StockTransfer::where('status', 'pending')->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmPartialReception(form, maxQty) {
            var input = form.querySelector('input[name="quantity_received"]');
            var qty = input ? parseInt(input.value, 10) : 0;
            if (isNaN(qty) || qty < 1 || qty > maxQty) {
                alert('Please enter a quantity between 1 and ' + maxQty + '.');
                return false;
            }
            return confirm('Record ' + qty + ' of ' + maxQty +
                ' units received? The sender must confirm before stock is credited.');
        }
    </script>
@endsection
