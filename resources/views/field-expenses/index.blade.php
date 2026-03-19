@extends('layouts.app')

@section('title', 'Field Expenses')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Field Expenses</h1>
        <a href="{{ route('field-expenses.create') }}" class="inline-flex items-center bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">New expense</a>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
        <form method="GET" action="{{ route('field-expenses.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="w-44">
                <label class="block text-sm font-medium text-themeBody mb-2">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
            </div>
            <div class="w-44">
                <label class="block text-sm font-medium text-themeBody mb-2">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
            </div>
            <div class="w-44">
                <label class="block text-sm font-medium text-themeBody mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
                    <option value="">All</option>
                    @foreach (['submitted', 'approved', 'rejected', 'paid'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Filter</button>
        </form>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-themeBorder">
            <thead class="bg-themeInput/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outlet</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Category</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Status</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-themeBorder">
            @forelse ($rows as $row)
                <tr class="hover:bg-themeInput/30 transition">
                    <td class="px-4 py-3 text-sm text-themeBody">{{ optional($row->expense_date)->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row->outlet?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row->category }}</td>
                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row->currency }} {{ number_format((float) $row->amount, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-themeBody">{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-themeMuted">No field expenses found.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if ($rows->hasPages())
            <div class="px-4 py-3 border-t border-themeBorder">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
