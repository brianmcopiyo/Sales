@extends('layouts.app')

@section('title', 'Bill – ' . ($bill->invoice_number ?: $bill->vendor?->name))

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('bills.index'), 'label' => 'Back to Bills'])
        <div class="flex justify-between items-start flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Bill</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $bill->vendor?->name }} · {{ $bill->invoice_number ?: 'No invoice #' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if($canEdit ?? false)
                    <a href="{{ route('bills.edit', $bill) }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">Edit</a>
                @endif
                @if($canApprove ?? false)
                    <form action="{{ route('bills.approve', $bill) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm">Approve</button>
                    </form>
                    <div class="inline" x-data="{ open: false }">
                        <button type="button" @click="open = true" class="bg-red-100 text-red-700 px-5 py-2.5 rounded-xl font-medium hover:bg-red-200 transition">Reject</button>
                        <template x-teleport="body">
                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="open = false">
                                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-xl" @click.stop>
                                    <h3 class="text-lg font-semibold text-themeHeading mb-2">Reject bill?</h3>
                                    <form action="{{ route('bills.reject', $bill) }}" method="POST">
                                        @csrf
                                        <label class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                        <textarea name="rejection_reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4" placeholder="Optional reason..."></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-xl font-medium">Reject</button>
                                            <button type="button" @click="open = false" class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif
                @if($canPay ?? false)
                    @php
                        $openMarkPaidModal = request('mark_paid') && $bill->isApproved();
                    @endphp
                    <div class="inline" x-data="{ open: {{ $openMarkPaidModal ? 'true' : 'false' }} }">
                        <button type="button" @click="open = true" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Mark as paid</button>
                        <template x-teleport="body">
                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="open = false">
                                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-xl" @click.stop>
                                    <h3 class="text-lg font-semibold text-themeHeading mb-4">Mark as paid</h3>
                                    <form action="{{ route('bills.pay', $bill) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <label class="block text-sm font-medium text-themeBody mb-2">Paid date *</label>
                                        <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4">
                                        <label class="block text-sm font-medium text-themeBody mb-2">Payment reference</label>
                                        <input type="text" name="payment_reference" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-4" placeholder="e.g. bank ref, check #">
                                        <label class="block text-sm font-medium text-themeBody mb-2">Evidence (optional)</label>
                                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl mb-1">
                                        <p class="text-xs text-themeMuted mb-4">PDF or image. Max 5 MB.</p>
                                        @error('evidence')
                                            <p class="text-red-500 text-sm mb-4">{{ $message }}</p>
                                        @enderror
                                        <div class="flex gap-2">
                                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl font-medium">Save</button>
                                            <button type="button" @click="open = false" class="bg-themeHover text-themeBody px-4 py-2 rounded-xl font-medium">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Vendor</dt>
                    <dd class="text-themeHeading font-medium">{{ $bill->vendor?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Invoice number</dt>
                    <dd class="text-themeHeading">{{ $bill->invoice_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Invoice date</dt>
                    <dd class="text-themeHeading">{{ $bill->invoice_date?->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Due date</dt>
                    <dd class="text-themeHeading {{ $bill->isOverdue() ? 'text-red-600 font-medium' : '' }}">{{ $bill->due_date?->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Amount</dt>
                    <dd class="text-primary font-semibold">{{ $bill->currency }} {{ number_format($bill->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Category</dt>
                    <dd class="text-themeHeading">{{ $bill->category?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Branch</dt>
                    <dd class="text-themeHeading">{{ $bill->branch?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Status</dt>
                    <dd>
                        @if($bill->status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Paid</span>
                            @if($bill->paid_at)
                                <span class="text-sm text-themeMuted ml-1">on {{ $bill->paid_at->format('M d, Y') }}</span>
                                @if($bill->payment_reference)
                                    <span class="text-sm text-themeMuted"> · {{ $bill->payment_reference }}</span>
                                @endif
                            @endif
                        @elseif($bill->status === 'approved')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-sky-100 text-sky-800">Approved</span>
                        @elseif($bill->status === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                            @if($bill->rejection_reason)
                                <p class="text-sm text-themeMuted mt-1">{{ $bill->rejection_reason }}</p>
                            @endif
                        @elseif($bill->status === 'pending_approval')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending approval</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-themeHover text-themeBody">{{ $bill->status }}</span>
                        @endif
                    </dd>
                </div>
                @if($bill->description)
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-themeMuted">Description</dt>
                        <dd class="text-themeBody">{{ $bill->description }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if($bill->attachments->isNotEmpty())
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-themeHeading mb-4">Attachments</h2>
                <ul class="space-y-2">
                    @foreach($bill->attachments as $att)
                        <li>
                            <a href="{{ route('bills.attachments.download', $att) }}" class="inline-flex items-center gap-2 text-primary font-medium hover:underline">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ $att->original_name ?: basename($att->file_path) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
