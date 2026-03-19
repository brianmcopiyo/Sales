@extends('layouts.app')

@section('title', 'Sale Details')

@section('content')
    <div class="w-full space-y-6" x-data="{ previewOpen: false, previewSrc: '', previewName: '', previewDownloadUrl: '', completeModalOpen: {{ $errors->has('completion_document') ? 'true' : 'false' }} }">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Sale Details</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $sale->sale_number ?? 'Sale' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (($canEditSale ?? false) && ($canCompleteSale ?? false) && $sale->status === 'pending')
                    <button type="button" @click="completeModalOpen = true"
                        class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Complete Sale</span>
                    </button>
                    @if ($canCancelSale ?? false)
                        <form action="{{ route('sales.cancel', $sale) }}" method="post" class="inline"
                            onsubmit="return confirm('Cancel this pending sale? Stock will be returned.');">
                            @csrf
                            <button type="submit"
                                class="bg-red-100 text-red-800 px-5 py-2.5 rounded-xl font-medium hover:bg-red-200 transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Cancel sale</span>
                            </button>
                        </form>
                    @endif
                @elseif (($canEditSale ?? false) && $sale->status === 'cancelled')
                    <form action="{{ route('sales.reopen', $sale) }}" method="post" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Reopen sale</span>
                        </button>
                    </form>
                    <span class="text-sm text-themeMuted">Reopen to edit or complete this sale.</span>
                @endif
                <a href="{{ route('sales.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Sale Information Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Sale Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Sale Number</div>
                            <div class="text-base font-medium text-themeHeading">{{ $sale->sale_number ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($sale->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-themeHover text-themeBody') }}">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Customer</div>
                            @if ($sale->customer)
                                <div class="text-base font-medium text-themeHeading">{{ $sale->customer->name }}</div>
                            @else
                                <span class="text-base font-medium text-themeMuted">—</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                            <div class="text-base font-medium text-themeHeading">{{ $sale->branch->name }}</div>
                        </div>
                        @if ($sale->outlet_id)
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Outlet</div>
                            <a href="{{ route('outlets.show', $sale->outlet) }}" class="text-base font-medium text-primary hover:underline">{{ $sale->outlet->name }}</a>
                        </div>
                        @endif
                        @if ($sale->check_in_id)
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Check-in (visit)</div>
                            <div class="text-base font-medium text-themeHeading">{{ $sale->checkIn->check_in_at->format('d M Y H:i') }} at {{ $sale->checkIn->outlet?->name ?? '—' }}</div>
                        </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Sold By</div>
                            <div class="text-base font-medium text-themeHeading">{{ $sale->soldBy->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Field Agent</div>
                            <div class="text-base font-medium text-themeHeading">
                                {{ $sale->items->first()?->fieldAgent?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Date</div>
                            <div class="text-base font-medium text-themeHeading">{{ $sale->created_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>

                @if (!$sale->customer && ($canEditSale ?? false) && ($customersForAttach ?? collect())->isNotEmpty())
                    <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-amber-800 tracking-tight mb-2">Attach customer</h2>
                        <p class="text-sm text-amber-700 mb-4">This sale has no customer. Select a customer to attach.</p>
                        <form action="{{ route('sales.attach-customer', $sale) }}" method="post" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div class="flex-1 min-w-[200px]">
                                <label for="attach_customer_id" class="block text-xs font-medium text-amber-800 mb-1">Customer</label>
                                <select id="attach_customer_id" name="customer_id" required
                                    class="w-full px-4 py-2.5 border border-amber-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400/50 font-medium text-themeHeading bg-white">
                                    <option value="">— Select customer —</option>
                                    @foreach ($customersForAttach as $c)
                                        <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}@if($c->phone) ({{ $c->phone }})@endif</option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="bg-amber-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-700 transition">
                                Attach customer
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Items Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Items</h2>
                    <div class="space-y-3">
                        @foreach ($sale->items as $item)
                            <div
                                class="flex justify-between items-start py-3 border-b border-themeBorder last:border-0 last:pb-0">
                                <div>
                                    <div class="font-medium text-themeHeading">{{ $item->product->name }}</div>
                                    <div class="text-sm font-medium text-themeMuted">{{ $item->quantity }} × {{ $currencySymbol }}
                                        {{ number_format($item->unit_price, 2) }}</div>
                                    @if ((float) ($item->unit_license_cost ?? 0) > 0)
                                        <div class="text-sm font-medium text-themeBody mt-1">Cost to sell: {{ $currencySymbol }} {{ number_format($item->total_license_cost, 2) }}</div>
                                    @endif
                                    @if ($item->fieldAgent)
                                        <div class="text-sm font-medium text-themeBody mt-1">
                                            Field Agent: {{ $item->fieldAgent->name }} (Commission: {{ $currencySymbol }}
                                            {{ number_format((float) $item->commission_amount, 2) }})
                                        </div>
                                    @endif
                                </div>
                                <div class="font-semibold text-primary">{{ $currencySymbol }} {{ number_format($item->subtotal, 2) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Summary Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Summary</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <div class="font-medium text-themeBody">Subtotal</div>
                            <div class="font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($sale->subtotal, 2) }}</div>
                        </div>
                        @if ($sale->tax > 0)
                            <div class="flex justify-between items-center">
                                <div class="font-medium text-themeBody">Tax</div>
                                <div class="font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($sale->tax, 2) }}</div>
                            </div>
                        @endif
                        @if ($sale->discount > 0)
                            <div class="flex justify-between items-center">
                                <div class="font-medium text-themeBody">Discount</div>
                                <div class="font-medium text-themeHeading">-{{ $currencySymbol }} {{ number_format($sale->discount, 2) }}</div>
                            </div>
                        @endif
                        @php $totalCommission = $sale->items->sum('commission_amount'); @endphp
                        @if ($totalCommission > 0)
                            <div class="flex justify-between items-center">
                                <div class="font-medium text-themeBody">Commission</div>
                                <div class="font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($totalCommission, 2) }}</div>
                            </div>
                        @endif
                        @php $totalCostToSell = $sale->total_cost_to_sell; @endphp
                        @if ($totalCostToSell > 0)
                            <div class="flex justify-between items-center">
                                <div class="font-medium text-themeBody">Total cost to sell (buying + license)</div>
                                <div class="font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($totalCostToSell, 2) }}</div>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="font-medium text-themeBody">Gross profit</div>
                                <div class="font-medium text-emerald-600">{{ $currencySymbol }} {{ number_format($sale->gross_profit, 2) }}</div>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-4 border-t border-themeBorder">
                            <div class="text-lg font-semibold text-primary">Total</div>
                            <div class="text-lg font-semibold text-primary">{{ $currencySymbol }} {{ number_format($sale->total, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                @if ($sale->notes)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Notes</h2>
                        <div class="font-medium text-themeBody whitespace-pre-wrap">{{ $sale->notes }}</div>
                    </div>
                @endif

                <!-- Attachments -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Attachments</h2>
                    @if ($sale->evidence && $sale->evidence->isNotEmpty())
                        @php
                            $byType = $sale->evidence->groupBy(fn($a) => $a->attachment_type ?? \App\Models\SaleAttachment::TYPE_INITIATION);
                            $initiation = $byType->get(\App\Models\SaleAttachment::TYPE_INITIATION, collect());
                            $completion = $byType->get(\App\Models\SaleAttachment::TYPE_COMPLETION, collect());
                        @endphp
                        @if ($initiation->isNotEmpty())
                            <div class="mb-4">
                                <h3 class="text-sm font-medium text-themeMuted mb-2">Initiation</h3>
                                <ul class="flex flex-wrap gap-3">
                                    @foreach ($initiation as $att)
                                        @php $previewUrl = $att->isImage() ? asset('storage/' . $att->file_path) : null; @endphp
                                        <li class="flex flex-col items-start">
                                            @if ($att->isImage())
                                                <button type="button"
                                                    @click="previewOpen = true; previewSrc = '{{ $previewUrl }}'; previewName = '{{ addslashes($att->file_name) }}'; previewDownloadUrl = '{{ route('sales.evidence.download', $att) }}'"
                                                    class="block rounded-lg border border-themeBorder overflow-hidden hover:ring-2 hover:ring-[#006F78]/30 focus:outline-none focus:ring-2 focus:ring-primary/40 transition">
                                                    <img src="{{ $previewUrl }}" alt="{{ $att->file_name }}"
                                                        class="h-20 w-20 object-cover bg-themeInput">
                                                </button>
                                                <a href="{{ route('sales.evidence.download', $att) }}"
                                                    class="mt-1 text-xs font-medium text-primary hover:underline truncate max-w-[120px]"
                                                    title="{{ $att->file_name }}">
                                                    {{ $att->file_name }}
                                                </a>
                                            @else
                                                <a href="{{ route('sales.evidence.download', $att) }}"
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
                        @endif
                        @if ($completion->isNotEmpty())
                            <div>
                                <h3 class="text-sm font-medium text-themeMuted mb-2">Completion</h3>
                                <ul class="flex flex-wrap gap-3">
                                    @foreach ($completion as $att)
                                        @php $previewUrl = $att->isImage() ? asset('storage/' . $att->file_path) : null; @endphp
                                        <li class="flex flex-col items-start">
                                            @if ($att->isImage())
                                                <button type="button"
                                                    @click="previewOpen = true; previewSrc = '{{ $previewUrl }}'; previewName = '{{ addslashes($att->file_name) }}'; previewDownloadUrl = '{{ route('sales.evidence.download', $att) }}'"
                                                    class="block rounded-lg border border-themeBorder overflow-hidden hover:ring-2 hover:ring-[#006F78]/30 focus:outline-none focus:ring-2 focus:ring-primary/40 transition">
                                                    <img src="{{ $previewUrl }}" alt="{{ $att->file_name }}"
                                                        class="h-20 w-20 object-cover bg-themeInput">
                                                </button>
                                                <a href="{{ route('sales.evidence.download', $att) }}"
                                                    class="mt-1 text-xs font-medium text-primary hover:underline truncate max-w-[120px]"
                                                    title="{{ $att->file_name }}">
                                                    {{ $att->file_name }}
                                                </a>
                                            @else
                                                <a href="{{ route('sales.evidence.download', $att) }}"
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
                        @endif
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
                                    <a :href="previewDownloadUrl"
                                        class="bg-primary text-white px-4 py-2 rounded-lg font-medium hover:bg-primary-dark transition">
                                        Download
                                    </a>
                                    <button type="button" @click="previewOpen = false"
                                        class="bg-themeHover text-themeBody px-4 py-2 rounded-lg font-medium transition">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-themeMuted text-sm">No attachments for this sale.</p>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Links Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        @if ($sale->customer_id)
                            <a href="{{ route('customers.show', $sale->customer) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>View Customer</span>
                            </a>
                        @endif
                        <a href="{{ route('branches.show', $sale->branch) }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            <span>View Branch</span>
                        </a>
                        <a href="{{ route('tickets.create') }}?sale={{ $sale->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <span>Create Ticket</span>
                        </a>
                        <a href="{{ route('sales.index') }}?branch={{ $sale->branch_id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>View Branch Sales</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Items</div>
                            <div class="text-2xl font-semibold text-primary">{{ $sale->items->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Quantity</div>
                            <div class="text-2xl font-semibold text-primary">{{ $sale->items->sum('quantity') }}</div>
                        </div>
                        @if ($sale->customer)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Customer Sales</div>
                                <div class="text-2xl font-semibold text-amber-600">
                                    {{ \App\Models\Sale::where('customer_id', $sale->customer_id)->where('status', 'completed')->count() }}
                                </div>
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branch Sales Today</div>
                            <div class="text-2xl font-semibold text-amber-600">
                                {{ \App\Models\Sale::where('branch_id', $sale->branch_id)->whereDate('created_at', today())->where('status', 'completed')->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Complete Sale modal --}}
        <div x-show="completeModalOpen" x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            aria-modal="true"
            @keydown.escape.window="completeModalOpen = false">
            <div x-show="completeModalOpen" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="absolute inset-0 bg-black/60" @click="completeModalOpen = false"></div>
            <div x-show="completeModalOpen" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-themeCard rounded-2xl border border-themeBorder shadow-xl w-full max-w-md p-6"
                @click.stop>
                <h3 class="text-lg font-semibold text-primary tracking-tight mb-1">Complete Sale</h3>
                <p class="text-sm text-themeMuted mb-4">Upload a completion document (image or PDF) to mark this sale as completed. This is required.</p>
                <form action="{{ route('sales.complete', $sale) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label for="completion_document" class="block text-sm font-medium text-themeBody mb-2">Document (required)</label>
                        <input type="file" name="completion_document" id="completion_document" required
                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,image/*,application/pdf"
                            class="block w-full text-sm text-themeBody file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-primary file:text-white file:cursor-pointer hover:file:bg-primary-dark border border-themeBorder rounded-xl bg-themeInput">
                        @error('completion_document')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-themeMuted">Max 5 MB. Images or PDF.</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="completeModalOpen = false"
                            class="px-4 py-2.5 rounded-xl font-medium bg-themeHover text-themeBody hover:bg-themeBorder transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2.5 rounded-xl font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            Complete Sale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

