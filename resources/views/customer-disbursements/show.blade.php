@extends('layouts.app')

@section('title', 'Disbursement Details')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Disbursement Details</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">TSh {{ number_format($customerDisbursement->amount, 2) }}
                    @if ($customerDisbursement->status === 'pending')
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-800 ml-2">Pending
                            approval</span>
                    @elseif($customerDisbursement->status === 'approved')
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800 ml-2">Approved</span>
                    @else
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-red-100 text-red-800 ml-2">Rejected</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if (($canApprove ?? false) && $customerDisbursement->isPending())
                    <form action="{{ route('customer-disbursements.approve', $customerDisbursement) }}" method="POST"
                        class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Approve</button>
                    </form>
                    <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="bg-red-100 text-red-700 px-5 py-2.5 rounded-xl font-medium hover:bg-red-200 transition">Reject</button>
                @endif
                <a href="{{ route('customer-disbursements.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18">
                        </path>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>

        @if (($canApprove ?? false) && $customerDisbursement->isPending())
            <div id="reject-form"
                class="hidden bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Reject disbursement</h2>
                <form action="{{ route('customer-disbursements.reject', $customerDisbursement) }}" method="POST"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="block text-sm font-medium text-themeBody mb-2">Reason
                            (optional)</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('rejection_reason') }}</textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                            class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-red-700 transition">Confirm
                            reject</button>
                        <button type="button" onclick="document.getElementById('reject-form').classList.add('hidden')"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</button>
                    </div>
                </form>
            </div>
        @endif

        @if ($customerDisbursement->status === 'rejected' && $customerDisbursement->sale && $customerDisbursement->sale->status === 'cancelled')
            <div class="rounded-xl border border-primary/30 bg-primary/5 px-4 py-4 mb-6">
                <p class="text-sm font-medium text-themeBody mb-2">This disbursement was rejected and the sale was cancelled. You can reopen the sale to submit a new disbursement request.</p>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <form action="{{ route('sales.reopen', $customerDisbursement->sale) }}" method="post" class="inline">
                        @csrf
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition text-sm">
                            Reopen sale
                        </button>
                    </form>
                    <a href="{{ route('sales.show', $customerDisbursement->sale) }}" class="text-primary font-medium hover:underline text-sm">View sale #{{ $customerDisbursement->sale->sale_number }}</a>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Disbursement Information -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Disbursement Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            @if ($customerDisbursement->status === 'pending')
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium bg-amber-100 text-amber-800">Pending</span>
                            @elseif($customerDisbursement->status === 'approved')
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium bg-emerald-100 text-emerald-800">Approved</span>
                                @if ($customerDisbursement->approved_at)
                                    <div class="text-xs font-medium text-themeMuted mt-1">
                                        {{ $customerDisbursement->approved_at->format('M d, Y h:i A') }} by
                                        {{ $customerDisbursement->approvedBy->name ?? '-' }}</div>
                                @endif
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium bg-red-100 text-red-800">Rejected</span>
                                @if ($customerDisbursement->rejected_at)
                                    <div class="text-xs font-medium text-themeMuted mt-1">
                                        {{ $customerDisbursement->rejected_at->format('M d, Y h:i A') }} by
                                        {{ $customerDisbursement->rejectedBy->name ?? '-' }}</div>
                                @endif
                                @if ($customerDisbursement->rejection_reason)
                                    <div class="text-sm font-medium text-themeBody mt-2">
                                        {{ $customerDisbursement->rejection_reason }}</div>
                                @endif
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Amount</div>
                            <div class="text-2xl font-semibold text-amber-600">TSh
                                {{ number_format($customerDisbursement->amount, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                            <div class="text-base font-medium text-themeHeading">{{ $customerDisbursement->branch_for_display?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Created On</div>
                            <div class="text-base font-medium text-themeHeading">
                                {{ $customerDisbursement->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Disbursement Phone</div>
                            <div class="text-base font-medium text-themeHeading">
                                {{ $customerDisbursement->disbursement_phone ?? '-' }}</div>
                        </div>
                        @if ($customerDisbursement->notes)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Notes</div>
                                <div class="text-base font-medium text-themeBody">{{ $customerDisbursement->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($customerDisbursement->device)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Device</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">IMEI</div>
                                <div class="text-base font-medium text-themeHeading">{{ $customerDisbursement->device->imei }}
                                </div>
                            </div>
                            @if ($customerDisbursement->device->product)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Product</div>
                                    <div class="text-base font-medium text-themeHeading">
                                        {{ $customerDisbursement->device->product->name }}</div>
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                                @php $dStatus = $customerDisbursement->device->status === 'sold' ? 'bg-emerald-100 text-emerald-800' : ($customerDisbursement->device->status === 'assigned' ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody'); @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $dStatus }}">{{ ucfirst($customerDisbursement->device->status) }}</span>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Disbursement Status</div>
                                @if ($customerDisbursement->isApproved())
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Received</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">{{ ucfirst($customerDisbursement->status) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Customer Information -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Customer</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                            <div class="text-base font-medium text-themeHeading">{{ $customerDisbursement->customer->name }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                            <div class="text-base font-medium text-themeHeading">
                                {{ $customerDisbursement->customer->email ?? '-' }}</div>
                        </div>
                        @if ($customerDisbursement->customer->phone)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Phone</div>
                                <div class="text-base font-medium text-themeHeading">
                                    {{ $customerDisbursement->customer->phone }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Disbursed</div>
                            <div class="text-lg font-semibold text-red-600">TSh
                                {{ number_format($customerDisbursement->customer->total_disbursed ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                @if ($customerDisbursement->sale)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Linked Sale</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Sale Number</div>
                                <a href="{{ route('sales.show', $customerDisbursement->sale) }}"
                                    class="font-medium text-primary hover:text-primary-dark">
                                    {{ $customerDisbursement->sale->sale_number }}
                                </a>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Sale Total</div>
                                <div class="text-base font-medium text-themeHeading">TSh
                                    {{ number_format($customerDisbursement->sale->total, 2) }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Sale Date</div>
                                <div class="text-base font-medium text-themeHeading">
                                    {{ $customerDisbursement->sale->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($customerDisbursement->disbursedBy)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Disbursed By</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                                <div class="text-base font-medium text-themeHeading">
                                    {{ $customerDisbursement->disbursedBy->name }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                                <div class="text-base font-medium text-themeHeading">
                                    {{ $customerDisbursement->disbursedBy->email }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

