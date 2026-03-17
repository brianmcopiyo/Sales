@extends('layouts.app')

@section('title', 'Daily Call Report')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Daily Call Report (DCR)</h1>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <form method="GET" action="{{ route('dcr.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-56">
                    <label for="user_id" class="block text-sm font-medium text-themeBody mb-2">Rep (user) *</label>
                    <select id="user_id" name="user_id" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Select user</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="date" class="block text-sm font-medium text-themeBody mb-2">Date *</label>
                    <input type="date" id="date" name="date" value="{{ $date ?? today()->format('Y-m-d') }}" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">View report</button>
            </form>
        </div>

        @if ($report !== null)
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
                <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-themeHeading">{{ $report['user']->name }} — {{ \Carbon\Carbon::parse($report['date'])->format('d M Y') }}</h2>
                    <a href="{{ route('dcr.export', ['user_id' => $report['user']->id, 'date' => $report['date']]) }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Export Excel
                    </a>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-themeInput/50 rounded-xl p-4">
                        <div class="text-sm text-themeMuted">Planned</div>
                        <div class="text-xl font-semibold text-themeHeading">{{ $report['total_planned'] }}</div>
                    </div>
                    <div class="bg-themeInput/50 rounded-xl p-4">
                        <div class="text-sm text-themeMuted">Completed (with check-in)</div>
                        <div class="text-xl font-semibold text-emerald-600">{{ $report['completed_planned'] }}</div>
                    </div>
                    <div class="bg-themeInput/50 rounded-xl p-4">
                        <div class="text-sm text-themeMuted">Total check-ins</div>
                        <div class="text-xl font-semibold text-themeHeading">{{ $report['total_checked_in'] }}</div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outlet</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Planned</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Checked in</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Check-in time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Check-out time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-themeBorder">
                            @foreach ($report['rows'] as $row)
                                <tr class="hover:bg-themeInput/30">
                                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row['outlet']->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $row['planned'] ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $row['checked_in'] ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row['check_in_at'] ? $row['check_in_at']->format('H:i') : '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-themeBody">{{ $row['check_out_at'] ? $row['check_out_at']->format('H:i') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (empty($report['rows']))
                    <p class="py-6 text-center text-themeMuted">No planned visits or check-ins for this day.</p>
                @endif
            </div>
        @endif
    </div>
@endsection
