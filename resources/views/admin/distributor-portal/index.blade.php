@extends('layouts.app')

@section('title', 'Distributor Portal Management')

@section('content')
<div class="py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold" style="color:#111827;">Distributor Portal</h1>
            <p class="text-sm mt-0.5" style="color:#6b7280;">Manage external distributor portal accounts</p>
        </div>
        <a href="{{ route('admin.distributor-portal.create') }}"
           class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">
            + New Distributor
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by customer..."
               class="text-sm border rounded-lg px-3 py-2 focus:outline-none" style="border-color:#e5e7eb;">
        <select name="branch" class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
            <option value="">All Branches</option>
            @foreach ($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch') === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <select name="status" class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
            <option value="">All Status</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">Filter</button>
        @if (request()->hasAny(['search', 'branch', 'status']))
            <a href="{{ route('admin.distributor-portal.index') }}" class="text-sm px-4 py-2 rounded-lg border" style="border-color:#e5e7eb; color:#374151;">Clear</a>
        @endif
    </form>

    <div class="rounded-xl border shadow-sm overflow-hidden" style="background:#fff; border-color:#e5e7eb;">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Customer</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Portal User</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Branch</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Balance</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase" style="color:#6b7280;">Joined</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($profiles as $p)
                        <tr class="border-t hover:bg-gray-50" style="border-color:#f3f4f6;">
                            <td class="px-4 py-3 font-medium" style="color:#111827;">{{ $p->customer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs" style="color:#374151;">
                                <p>{{ $p->user?->name }}</p>
                                <p style="color:#6b7280;">{{ $p->user?->email ?? $p->user?->phone }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $p->assignedBranch?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-medium" style="color:#111827;">{{ number_format($p->outstanding_balance, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $p->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $p->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $p->portal_enabled_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.distributor-portal.show', $p) }}" class="text-xs font-medium hover:underline" style="color:#006F78;">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-sm" style="color:#6b7280;">No distributor portal accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($profiles->hasPages())
            <div class="px-4 py-3 border-t" style="border-color:#e5e7eb;">{{ $profiles->links() }}</div>
        @endif
    </div>
</div>
@endsection
