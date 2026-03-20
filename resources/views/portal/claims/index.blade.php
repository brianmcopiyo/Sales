@extends('layouts.portal')

@section('title', 'My Claims')

@section('content')
<div class="py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-xl font-bold" style="color:#111827;">My Claims</h1>
        <a href="{{ route('portal.claims.create') }}"
           class="text-sm px-4 py-2 rounded-lg font-medium text-white transition-opacity hover:opacity-90"
           style="background-color:#006F78;">
            + New Claim
        </a>
    </div>

    {{-- Status Filter --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="status" class="text-sm border rounded-lg px-3 py-2 focus:outline-none" style="border-color:#e5e7eb;">
            <option value="">All Statuses</option>
            @foreach (\App\Models\DistributorClaim::STATUSES as $val => $label)
                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">
            Filter
        </button>
        @if (request('status'))
            <a href="{{ route('portal.claims.index') }}" class="text-sm px-4 py-2 rounded-lg border font-medium"
               style="border-color:#e5e7eb; color:#374151;">Clear</a>
        @endif
    </form>

    <div class="space-y-4">
        @forelse ($claims as $claim)
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
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <a href="{{ route('portal.claims.show', $claim) }}"
                               class="text-sm font-semibold hover:underline" style="color:#006F78;">
                                {{ $claim->claim_number }}
                            </a>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $claim->getStatusLabel() }}
                            </span>
                        </div>
                        <p class="text-xs font-medium" style="color:#374151;">{{ $claim->getTypeLabel() }}</p>
                        <p class="text-xs mt-1 line-clamp-2" style="color:#6b7280;">{{ $claim->description }}</p>
                    </div>
                    <div class="text-right">
                        @if ($claim->amount_claimed)
                            <p class="text-xs" style="color:#6b7280;">Claimed</p>
                            <p class="text-sm font-semibold" style="color:#111827;">{{ number_format($claim->amount_claimed, 2) }}</p>
                        @endif
                        @if ($claim->amount_approved)
                            <p class="text-xs mt-1" style="color:#6b7280;">Approved</p>
                            <p class="text-sm font-semibold text-green-600">{{ number_format($claim->amount_approved, 2) }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t" style="border-color:#f3f4f6;">
                    <p class="text-xs" style="color:#6b7280;">{{ $claim->created_at->format('M d, Y') }}</p>
                    @if ($claim->attachments->isNotEmpty())
                        <p class="text-xs" style="color:#6b7280;">{{ $claim->attachments->count() }} attachment(s)</p>
                    @endif
                    <a href="{{ route('portal.claims.show', $claim) }}" class="text-xs font-medium hover:underline" style="color:#006F78;">
                        View Details &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-xl border p-12 text-center" style="background:#fff; border-color:#e5e7eb;">
                <p class="text-sm font-medium" style="color:#374151;">No claims submitted yet.</p>
                <p class="text-sm mt-1" style="color:#6b7280;">Have an issue with a delivery or shipment?</p>
                <a href="{{ route('portal.claims.create') }}"
                   class="inline-block mt-4 text-sm px-4 py-2 rounded-lg font-medium text-white"
                   style="background-color:#006F78;">
                    Submit a Claim
                </a>
            </div>
        @endforelse
    </div>

    @if ($claims->hasPages())
        <div class="mt-5">{{ $claims->links() }}</div>
    @endif
</div>
@endsection
