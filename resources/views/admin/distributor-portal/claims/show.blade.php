@extends('layouts.app')

@section('title', $claim->claim_number)

@section('content')
<div class="py-6 max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.distributor-portal.claims.index') }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back to Claims</a>
    </div>

    @php
        $sc = ['pending'=>'bg-yellow-100 text-yellow-800','under_review'=>'bg-blue-100 text-blue-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800','settled'=>'bg-gray-100 text-gray-700'];
    @endphp

    <div class="rounded-xl border shadow-sm p-6 mb-5" style="background:#fff; border-color:#e5e7eb;">
        <div class="flex items-start justify-between mb-5">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold" style="color:#111827;">{{ $claim->claim_number }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc[$claim->status] ?? '' }}">{{ $claim->getStatusLabel() }}</span>
                </div>
                <p class="text-sm" style="color:#6b7280;">{{ $claim->distributorProfile?->customer?->name }} — {{ $claim->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5 text-sm">
            <div><p class="text-xs text-gray-500 mb-0.5">Type</p><p class="font-medium">{{ $claim->getTypeLabel() }}</p></div>
            @if ($claim->amount_claimed)
            <div><p class="text-xs text-gray-500 mb-0.5">Amount Claimed</p><p class="font-semibold">{{ number_format($claim->amount_claimed, 2) }}</p></div>
            @endif
            @if ($claim->referenceSale)
            <div><p class="text-xs text-gray-500 mb-0.5">Related Order</p><p class="font-medium">{{ $claim->referenceSale->sale_number ?? $claim->referenceSale->id }}</p></div>
            @endif
        </div>

        <div class="mb-5">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Description</p>
            <div class="rounded-lg p-4 text-sm whitespace-pre-wrap" style="background:#f9fafb;">{{ $claim->description }}</div>
        </div>

        @if ($claim->attachments->isNotEmpty())
        <div class="mb-5">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Attachments</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($claim->attachments as $att)
                <a href="{{ Storage::url($att->file_path) }}" target="_blank"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-medium hover:bg-gray-50"
                   style="border-color:#e5e7eb; color:#374151;">
                    {{ $att->isImage() ? '🖼' : '📄' }} {{ $att->original_filename }}
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Approve / Reject --}}
    @if (in_array($claim->status, ['pending', 'under_review']))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold mb-3 text-green-700">Approve Claim</h2>
                <form method="POST" action="{{ route('admin.distributor-portal.claims.approve', $claim) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#374151;">Amount to Approve <span class="text-red-500">*</span></label>
                        <input type="number" name="amount_approved" step="0.01" min="0"
                               value="{{ $claim->amount_claimed }}" required
                               class="w-full text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#374151;">Notes</label>
                        <textarea name="reviewer_notes" rows="2" class="w-full text-sm border rounded-lg px-3 py-2 resize-none" style="border-color:#e5e7eb;"></textarea>
                    </div>
                    <button type="submit" class="w-full text-sm py-2 rounded-lg font-medium text-white bg-green-600 hover:bg-green-700">
                        Approve
                    </button>
                </form>
            </div>

            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold mb-3 text-red-600">Reject Claim</h2>
                <form method="POST" action="{{ route('admin.distributor-portal.claims.reject', $claim) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:#374151;">Reason for Rejection <span class="text-red-500">*</span></label>
                        <textarea name="reviewer_notes" rows="4" required minlength="5"
                                  class="w-full text-sm border rounded-lg px-3 py-2 resize-none" style="border-color:#e5e7eb;"
                                  placeholder="Explain why this claim is being rejected..."></textarea>
                    </div>
                    <button type="submit" class="w-full text-sm py-2 rounded-lg font-medium text-white bg-red-500 hover:bg-red-600">
                        Reject
                    </button>
                </form>
            </div>
        </div>
    @elseif ($claim->reviewer)
        <div class="rounded-xl border p-5 shadow-sm" style="background:#f9fafb; border-color:#e5e7eb;">
            <p class="text-sm font-medium" style="color:#374151;">Reviewed by {{ $claim->reviewer->name }} on {{ $claim->reviewed_at?->format('M d, Y') }}</p>
            @if ($claim->reviewer_notes)
                <p class="text-sm mt-1" style="color:#6b7280;">{{ $claim->reviewer_notes }}</p>
            @endif
            @if ($claim->amount_approved)
                <p class="text-sm mt-2 font-semibold text-green-600">Approved amount: {{ number_format($claim->amount_approved, 2) }}</p>
            @endif
        </div>
    @endif
</div>
@endsection
