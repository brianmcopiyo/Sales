@extends('layouts.app')

@section('title', 'Check-in History')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Check-in History</h1>
            @if (auth()->user()?->hasPermission('checkins.create'))
                <a href="{{ route('check-ins.create') }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    New check-in
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
            <form method="GET" action="{{ route('check-ins.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-44">
                    <label for="user_id" class="block text-sm font-medium text-themeBody mb-2">User</label>
                    <select id="user_id" name="user_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary bg-themeCard">
                        <option value="">All users</option>
                        @foreach ($users ?? [] as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-52">
                    <label for="outlet_id" class="block text-sm font-medium text-themeBody mb-2">Outlet</label>
                    <select id="outlet_id" name="outlet_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary bg-themeCard">
                        <option value="">All outlets</option>
                        @foreach ($outlets ?? [] as $o)
                            <option value="{{ $o->id }}" {{ request('outlet_id') == $o->id ? 'selected' : '' }}>{{ $o->name }}{{ $o->code ? ' (' . $o->code . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="w-44">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Filter</button>
                @if (request()->hasAny(['date_from', 'date_to', 'outlet_id', 'user_id']))
                    <a href="{{ route('check-ins.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Date / Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Notes</th>
                            @if (auth()->user()?->hasPermission('outlets.view') || auth()->user()?->hasPermission('outlets.manage') || auth()->user()?->hasPermission('distribution.reports'))
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($checkIns as $ci)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $ci->check_in_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $ci->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $ci->outlet?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeMuted">{{ number_format((float) $ci->lat_in, 5) }}, {{ number_format((float) $ci->lng_in, 5) }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody max-w-xs truncate">{{ $ci->notes ?? '—' }}</td>
                                @if (auth()->user()?->hasPermission('outlets.view') || auth()->user()?->hasPermission('outlets.manage') || auth()->user()?->hasPermission('distribution.reports'))
                                    <td class="px-4 py-3 text-sm">
                                        @if ($ci->auditRuns()->exists())
                                            <a href="{{ route('audit-runs.show', $ci->auditRuns->first()) }}" class="text-primary hover:underline">View audit</a>
                                        @else
                                            <a href="{{ route('audit-runs.create', ['check_in_id' => $ci->id]) }}" class="text-primary hover:underline">Start audit</a>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ (auth()->user()?->hasPermission('outlets.view') || auth()->user()?->hasPermission('outlets.manage') || auth()->user()?->hasPermission('distribution.reports')) ? 6 : 5 }}" class="px-4 py-12 text-center text-themeMuted">No check-ins found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($checkIns->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $checkIns->links() }}</div>
            @endif
        </div>
    </div>
@endsection
