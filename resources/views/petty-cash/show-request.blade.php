@extends('layouts.app')

@section('title', 'Petty Cash Request')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Petty Cash Request</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">
                    {{ $pettyCashRequest->fund->currency }} {{ number_format($pettyCashRequest->amount, 2) }}
                    @if($pettyCashRequest->status === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-800 ml-2">Pending</span>
                    @elseif($pettyCashRequest->status === 'approved')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-sky-100 text-sky-800 ml-2">Approved</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-amber-100 text-amber-800 ml-2">Pending payment</span>
                    @elseif($pettyCashRequest->status === 'rejected')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-red-100 text-red-800 ml-2">Rejected</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800 ml-2">Disbursed</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800 ml-2">Paid</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if(($canApprove ?? false) && $pettyCashRequest->isPending())
                    <form action="{{ route('petty-cash.approve', $pettyCashRequest) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Approve</button>
                    </form>
                    <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="bg-red-100 text-red-700 px-5 py-2.5 rounded-xl font-medium hover:bg-red-200 transition">Reject</button>
                @endif
        @if($canMarkAsPaid ?? false)
            <button type="button" onclick="document.getElementById('disburse-form').classList.toggle('hidden')"
                class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition">Mark as paid</button>
        @endif
        @if($canUploadProof ?? false)
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-100 text-amber-800">Upload proof of expenditure below</span>
        @endif
        <a href="{{ route('petty-cash.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>

        @if(($canApprove ?? false) && $pettyCashRequest->isPending())
            <div id="reject-form" class="hidden bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Reject request</h2>
                <form action="{{ route('petty-cash.reject', $pettyCashRequest) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('rejection_reason') }}</textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-red-700 transition">Confirm reject</button>
                        <button type="button" onclick="document.getElementById('reject-form').classList.add('hidden')"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</button>
                    </div>
                </form>
            </div>
        @endif

        @if($canMarkAsPaid ?? false)
            <div id="disburse-form" class="hidden bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Record payment</h2>
                <form action="{{ route('petty-cash.disburse', $pettyCashRequest) }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-sm text-themeBody">Confirm you have given {{ $pettyCashRequest->fund->currency }} {{ number_format($pettyCashRequest->amount, 2) }} to {{ $pettyCashRequest->requestedByUser->name }}.</p>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition">Mark as paid</button>
                        <button type="button" onclick="document.getElementById('disburse-form').classList.add('hidden')"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Request Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                            <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->fund->branch->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Amount</div>
                            <div class="text-2xl font-semibold text-amber-600">{{ $pettyCashRequest->fund->currency }} {{ number_format($pettyCashRequest->amount, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Category</div>
                            <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->category_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Requested On</div>
                            <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                        @if($pettyCashRequest->reason)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Reason</div>
                                <div class="text-base font-medium text-themeBody">{{ $pettyCashRequest->reason }}</div>
                            </div>
                        @endif
                        @if($pettyCashRequest->attachment_path)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Attachment</div>
                                <a href="{{ route('petty-cash.request.attachment', $pettyCashRequest) }}"
                                    class="inline-flex items-center gap-2 text-primary font-medium hover:underline">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Download attachment
                                </a>
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <div class="text-sm font-medium text-themeMuted mb-1">Requested By</div>
                            <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->requestedByUser->name }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Approval & Disbursement</h2>
                    <div class="space-y-4">
                        {{-- Payment status: paid or pending --}}
                        @if($pettyCashRequest->disbursed_at)
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
                                <div class="text-sm font-semibold text-emerald-800">Payment: Paid</div>
                                <div class="text-sm font-medium text-emerald-700 mt-1">Disbursed by {{ $pettyCashRequest->disbursedByUser?->name ?? '—' }} on {{ $pettyCashRequest->disbursed_at->format('M d, Y h:i A') }}</div>
                            </div>
                        @elseif($pettyCashRequest->approved_at)
                            <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                                <div class="text-sm font-semibold text-amber-800">Payment: Pending</div>
                                <div class="text-sm font-medium text-amber-700 mt-1">Approved; awaiting custodian to mark as paid.</div>
                            </div>
                        @endif
                        @if($pettyCashRequest->approved_at)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Approved By</div>
                                <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->approvedByUser?->name ?? '—' }}</div>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $pettyCashRequest->approved_at->format('M d, Y h:i A') }}</div>
                            </div>
                        @endif
                        @if($pettyCashRequest->rejected_at)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Rejected By</div>
                                <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->rejectedByUser?->name ?? '—' }}</div>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $pettyCashRequest->rejected_at->format('M d, Y h:i A') }}</div>
                                @if($pettyCashRequest->rejection_reason)
                                    <div class="text-sm font-medium text-themeBody mt-2">{{ $pettyCashRequest->rejection_reason }}</div>
                                @endif
                            </div>
                        @endif
                        @if($pettyCashRequest->disbursed_at)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Disbursed By</div>
                                <div class="text-base font-medium text-themeHeading">{{ $pettyCashRequest->disbursedByUser?->name ?? '—' }}</div>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $pettyCashRequest->disbursed_at->format('M d, Y h:i A') }}</div>
                            </div>
                        @endif
                        @if(!$pettyCashRequest->approved_at && !$pettyCashRequest->rejected_at && !$pettyCashRequest->disbursed_at)
                            <p class="text-sm text-themeMuted font-medium">Pending approval.</p>
                        @endif
                    </div>
                </div>

                @if($pettyCashRequest->isDisbursed() && ($isApplicant ?? false))
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Proof of expenditure</h2>
                        @if($pettyCashRequest->hasProofOfExpenditure())
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
                                <div class="text-sm font-semibold text-emerald-800">Proof uploaded</div>
                                <div class="text-sm font-medium text-emerald-700 mt-1">Uploaded on {{ $pettyCashRequest->proof_of_expenditure_uploaded_at?->format('M d, Y h:i A') ?? '—' }}</div>
                                <a href="{{ route('petty-cash.download-proof', $pettyCashRequest) }}"
                                    class="inline-flex items-center gap-2 text-primary font-medium hover:underline mt-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Download proof of expenditure
                                </a>
                            </div>
                        @elseif($canUploadProof ?? false)
                            <p class="text-sm text-themeBody mb-4">Upload a file (receipt, invoice, or photo) as proof of how you used the disbursed amount. You must do this before you can submit another petty cash request.</p>
                            <form action="{{ route('petty-cash.upload-proof', $pettyCashRequest) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="proof_of_expenditure" class="block text-sm font-medium text-themeBody mb-2">Proof file (PDF, JPG, PNG, etc. max 5MB)</label>
                                    <input type="file" id="proof_of_expenditure" name="proof_of_expenditure" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    @error('proof_of_expenditure')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Upload proof of expenditure</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
