@extends('layouts.app')

@section('title', $profile->customer?->name . ' — Distributor Portal')

@section('content')
<div class="py-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.distributor-portal.index') }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back</a>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold" style="color:#111827;">{{ $profile->customer?->name }}</h1>
            <p class="text-sm" style="color:#6b7280;">Distributor Portal Account</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.distributor-portal.edit', $profile) }}"
               class="text-sm px-4 py-2 rounded-lg border font-medium" style="border-color:#e5e7eb; color:#374151;">
                Edit
            </a>
            <span class="px-3 py-2 rounded-lg text-xs font-medium
                {{ $profile->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $profile->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border p-4 shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase">Revenue MTD</p>
            <p class="text-xl font-bold mt-1">{{ number_format($revenueMtd, 2) }}</p>
        </div>
        <div class="rounded-xl border p-4 shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase">Total Orders</p>
            <p class="text-xl font-bold mt-1">{{ $totalOrders }}</p>
        </div>
        <div class="rounded-xl border p-4 shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase">Outstanding Balance</p>
            <p class="text-xl font-bold mt-1">{{ number_format($profile->outstanding_balance, 2) }}</p>
        </div>
        <div class="rounded-xl border p-4 shadow-sm" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-xs text-gray-500 uppercase">Open Claims</p>
            <p class="text-xl font-bold mt-1">{{ $profile->claims->whereIn('status', ['pending', 'under_review'])->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profile Details --}}
        <div class="space-y-4">
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold mb-3" style="color:#111827;">Profile Info</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt style="color:#6b7280;">Portal User</dt>
                        <dd style="color:#374151;">{{ $profile->user?->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color:#6b7280;">Email</dt>
                        <dd style="color:#374151;">{{ $profile->user?->email ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color:#6b7280;">Phone</dt>
                        <dd style="color:#374151;">{{ $profile->user?->phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color:#6b7280;">Branch</dt>
                        <dd style="color:#374151;">{{ $profile->assignedBranch?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color:#6b7280;">Credit Limit</dt>
                        <dd style="color:#374151;">{{ $profile->credit_limit ? number_format($profile->credit_limit, 2) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt style="color:#6b7280;">Portal Enabled</dt>
                        <dd style="color:#374151;">{{ $profile->portal_enabled_at?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                </dl>
                @if ($profile->notes)
                    <div class="mt-3 pt-3 border-t" style="border-color:#f3f4f6;">
                        <p class="text-xs" style="color:#6b7280;">{{ $profile->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- Set Target --}}
            @can('permission', 'distributor-portal.targets.manage')
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold mb-3" style="color:#111827;">Set Target</h2>
                <form method="POST" action="{{ route('admin.distributor-portal.targets.store', $profile) }}" class="space-y-3">
                    @csrf
                    <select name="target_type" required class="w-full text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
                        @foreach (\App\Models\DistributorTarget::TARGET_TYPES as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <select name="period_type" required class="text-sm border rounded-lg px-2 py-2" style="border-color:#e5e7eb;">
                            @foreach (\App\Models\DistributorTarget::PERIOD_TYPES as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="period_year" value="{{ date('Y') }}" min="2020" max="2099" required
                               class="text-sm border rounded-lg px-2 py-2" style="border-color:#e5e7eb;" placeholder="Year">
                    </div>
                    <input type="number" name="period_value" value="1" min="1" max="12" required
                           class="w-full text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;" placeholder="Period (month/quarter)">
                    <input type="number" name="target_value" step="0.01" min="0" required
                           class="w-full text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;" placeholder="Target value">
                    <button type="submit" class="w-full text-sm py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">
                        Set Target
                    </button>
                </form>
            </div>
            @endcan
        </div>

        {{-- Targets + Claims --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Targets --}}
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold mb-3" style="color:#111827;">Targets</h2>
                @forelse ($profile->targets->sortByDesc('created_at') as $target)
                    <div class="flex items-center justify-between py-2 border-b last:border-0" style="border-color:#f3f4f6;">
                        <div class="text-sm">
                            <span class="font-medium" style="color:#374151;">{{ $target->getPeriodLabel() }}</span>
                            <span class="text-xs ml-2" style="color:#6b7280;">{{ $target->target_type }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium" style="color:#111827;">{{ number_format($target->target_value, 2) }}</span>
                            @can('permission', 'distributor-portal.targets.manage')
                            <form method="POST" action="{{ route('admin.distributor-portal.targets.destroy', [$profile, $target]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline">Remove</button>
                            </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-sm" style="color:#6b7280;">No targets set yet.</p>
                @endforelse
            </div>

            {{-- Recent Claims --}}
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold" style="color:#111827;">Claims</h2>
                    <a href="{{ route('admin.distributor-portal.claims.index') }}" class="text-xs font-medium" style="color:#006F78;">View all</a>
                </div>
                @forelse ($profile->claims->sortByDesc('created_at')->take(5) as $claim)
                    @php
                        $sc = ['pending'=>'text-yellow-700','under_review'=>'text-blue-700','approved'=>'text-green-700','rejected'=>'text-red-700','settled'=>'text-gray-600'];
                    @endphp
                    <div class="flex items-center justify-between py-2 border-b last:border-0" style="border-color:#f3f4f6;">
                        <div>
                            <a href="{{ route('admin.distributor-portal.claims.show', $claim) }}" class="text-sm font-medium hover:underline" style="color:#006F78;">
                                {{ $claim->claim_number }}
                            </a>
                            <p class="text-xs" style="color:#6b7280;">{{ $claim->getTypeLabel() }}</p>
                        </div>
                        <span class="text-xs font-medium {{ $sc[$claim->status] ?? '' }}">{{ $claim->getStatusLabel() }}</span>
                    </div>
                @empty
                    <p class="text-sm" style="color:#6b7280;">No claims submitted.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
