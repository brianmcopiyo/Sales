@extends('layouts.app')

@section('title', 'Planned Visits')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Planned Visits</h1>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('distribution.reports'))
                    <a href="{{ route('dcr.index') }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                        Daily Call Report
                    </a>
                @endif
                <a href="{{ route('planned-visits.create') }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Plan day
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif

        @if (request('user_id') && request('planned_date'))
            <div class="flex items-center gap-3">
                <form method="POST"
                    action="{{ route('planned-visits.optimize', [request('user_id'), request('planned_date')]) }}"
                    onsubmit="return confirm('Reorder visits by nearest GPS route?');">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-amber-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-amber-700 transition shadow-sm text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        Optimize Route
                    </button>
                </form>
                <span class="text-sm text-themeMuted">Reorders visits by shortest GPS route using outlet coordinates.</span>
            </div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
            <form method="GET" action="{{ route('planned-visits.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-48">
                    <label for="user_id" class="block text-sm font-medium text-themeBody mb-2">User</label>
                    <select id="user_id" name="user_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All</option>
                        @foreach ($users ?? [] as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="planned_date" class="block text-sm font-medium text-themeBody mb-2">Date</label>
                    <input type="date" id="planned_date" name="planned_date" value="{{ request('planned_date') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Filter</button>
            </form>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Sequence</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($plannedVisits as $pv)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $pv->planned_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $pv->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $pv->outlet?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $pv->sequence }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('planned-visits.destroy', $pv) }}" class="inline" onsubmit="return confirm('Remove this planned visit?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-themeMuted">No planned visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($plannedVisits->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $plannedVisits->links() }}</div>
            @endif
        </div>
    </div>
@endsection
