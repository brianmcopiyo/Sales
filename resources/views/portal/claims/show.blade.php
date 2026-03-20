@extends('layouts.portal')

@section('title', $claim->claim_number)

@section('content')
<div class="py-6 max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('portal.claims.index') }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back to Claims</a>
    </div>

    @php
        $statusColors = [
            'pending'      => 'bg-yellow-100 text-yellow-800',
            'under_review' => 'bg-blue-100 text-blue-800',
            'approved'     => 'bg-green-100 text-green-800',
            'rejected'     => 'bg-red-100 text-red-800',
            'settled'      => 'bg-gray-100 text-gray-700',
        ];
        $statusClass = $statusColors[$claim->status] ?? 'bg-gray-100 text-gray-700';
    @endphp

    <div class="rounded-xl border shadow-sm p-6" style="background:#fff; border-color:#e5e7eb;">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold" style="color:#111827;">{{ $claim->claim_number }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                        {{ $claim->getStatusLabel() }}
                    </span>
                </div>
                <p class="text-sm" style="color:#6b7280;">Submitted {{ $claim->created_at->format('F j, Y \a\t g:i A') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:#6b7280;">Claim Type</p>
                <p class="text-sm font-medium" style="color:#374151;">{{ $claim->getTypeLabel() }}</p>
            </div>
            @if ($claim->amount_claimed)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:#6b7280;">Amount Claimed</p>
                    <p class="text-sm font-semibold" style="color:#111827;">{{ number_format($claim->amount_claimed, 2) }}</p>
                </div>
            @endif
            @if ($claim->amount_approved)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:#6b7280;">Amount Approved</p>
                    <p class="text-sm font-semibold text-green-600">{{ number_format($claim->amount_approved, 2) }}</p>
                </div>
            @endif
            @if ($claim->referenceSale)
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:#6b7280;">Related Order</p>
                    <a href="{{ route('portal.orders.show', $claim->referenceSale) }}" class="text-sm font-medium hover:underline" style="color:#006F78;">
                        {{ $claim->referenceSale->sale_number ?? $claim->referenceSale->id }}
                    </a>
                </div>
            @endif
        </div>

        <div class="mb-5">
            <p class="text-xs font-medium uppercase tracking-wide mb-2" style="color:#6b7280;">Description</p>
            <div class="rounded-lg p-4 text-sm whitespace-pre-wrap" style="background:#f9fafb; color:#374151;">{{ $claim->description }}</div>
        </div>

        {{-- Attachments --}}
        @if ($claim->attachments->isNotEmpty())
            <div class="mb-5">
                <p class="text-xs font-medium uppercase tracking-wide mb-2" style="color:#6b7280;">Attachments</p>
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

        {{-- Reviewer Notes --}}
        @if ($claim->reviewer_notes)
            <div class="rounded-lg p-4 border" style="background:#f0fdf4; border-color:#bbf7d0;">
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:#15803d;">Review Notes</p>
                <p class="text-sm" style="color:#166534;">{{ $claim->reviewer_notes }}</p>
                @if ($claim->reviewer)
                    <p class="text-xs mt-2" style="color:#15803d;">— {{ $claim->reviewer->name }}, {{ $claim->reviewed_at?->format('M d, Y') }}</p>
                @endif
            </div>
        @endif

        @if ($claim->status === 'rejected')
            <div class="mt-4 p-4 rounded-lg border" style="background:#fef2f2; border-color:#fecaca;">
                <p class="text-sm font-medium" style="color:#991b1b;">Your claim was rejected.</p>
                <p class="text-xs mt-1" style="color:#dc2626;">If you believe this was in error, please contact your account manager.</p>
            </div>
        @endif
    </div>
</div>
@endsection
