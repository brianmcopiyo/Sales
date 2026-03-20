@extends('layouts.app')

@section('title', 'Distributor Claims')

@section('content')
<div class="py-6">
    <h1 class="text-xl font-bold mb-6" style="color:#111827;">Distributor Claims</h1>

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="status" class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
            <option value="">All Statuses</option>
            @foreach (\App\Models\DistributorClaim::STATUSES as $val => $label)
                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="type" class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
            <option value="">All Types</option>
            @foreach (\App\Models\DistributorClaim::TYPES as $val => $label)
                <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
        <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">Filter</button>
        @if (request()->hasAny(['status', 'type', 'date_from', 'date_to']))
            <a href="{{ route('admin.distributor-portal.claims.index') }}" class="text-sm px-4 py-2 rounded-lg border" style="border-color:#e5e7eb; color:#374151;">Clear</a>
        @endif
    </form>

    <div class="rounded-xl border shadow-sm overflow-hidden" style="background:#fff; border-color:#e5e7eb;">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Claim #</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Distributor</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Amount</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        @php
                            $sc = ['pending'=>'bg-yellow-100 text-yellow-800','under_review'=>'bg-blue-100 text-blue-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800','settled'=>'bg-gray-100 text-gray-700'];
                        @endphp
                        <tr class="border-t hover:bg-gray-50" style="border-color:#f3f4f6;">
                            <td class="px-4 py-3 font-medium" style="color:#374151;">{{ $claim->claim_number }}</td>
                            <td class="px-4 py-3 text-xs" style="color:#374151;">
                                <p>{{ $claim->distributorProfile?->customer?->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#374151;">{{ $claim->getTypeLabel() }}</td>
                            <td class="px-4 py-3 text-xs" style="color:#374151;">{{ $claim->amount_claimed ? number_format($claim->amount_claimed, 2) : '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$claim->status] ?? '' }}">{{ $claim->getStatusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $claim->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.distributor-portal.claims.show', $claim) }}" class="text-xs font-medium hover:underline" style="color:#006F78;">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-sm" style="color:#6b7280;">No claims found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($claims->hasPages())
            <div class="px-4 py-3 border-t" style="border-color:#e5e7eb;">{{ $claims->links() }}</div>
        @endif
    </div>
</div>
@endsection
