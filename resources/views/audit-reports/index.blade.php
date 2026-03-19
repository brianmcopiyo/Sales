@extends('layouts.app')

@section('title', 'Audit Reports')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Outlet audit reports</h1>
            <a href="{{ route('audit-templates.index') }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Templates</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-sm font-medium text-themeMuted mb-1">Total completed audits</div>
                <div class="text-2xl font-semibold text-themeHeading">{{ $stats['total_runs'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-sm font-medium text-themeMuted mb-1">Average compliance (%)</div>
                <div class="text-2xl font-semibold text-themeHeading">{{ $stats['avg_compliance'] !== null ? round($stats['avg_compliance'], 1) : '—' }}</div>
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
            <form method="GET" action="{{ route('audit-reports.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-44">
                    <label for="outlet_id" class="block text-sm font-medium text-themeBody mb-2">Outlet</label>
                    <select id="outlet_id" name="outlet_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All</option>
                        @foreach ($outlets as $o)
                            <option value="{{ $o->id }}" {{ request('outlet_id') == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="user_id" class="block text-sm font-medium text-themeBody mb-2">Rep</label>
                    <select id="user_id" name="user_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All</option>
                        @foreach ($reps as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="w-44">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="w-44">
                    <label for="template_id" class="block text-sm font-medium text-themeBody mb-2">Template</label>
                    <select id="template_id" name="template_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" {{ request('template_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="category" class="block text-sm font-medium text-themeBody mb-2">Category</label>
                    <select id="category" name="category" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All</option>
                        @foreach ($categories as $val => $label)
                            <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Filter</button>
                @if (request()->hasAny(['outlet_id', 'user_id', 'date_from', 'date_to', 'template_id', 'category']))
                    <a href="{{ route('audit-reports.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Rep</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Template</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($runs as $run)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $run->completed_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $run->checkIn->outlet->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $run->checkIn->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $run->template->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @php $cat = $run->template?->category ?? 'general'; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-themeHover text-themeBody">
                                        {{ \App\Models\AuditTemplate::categories()[$cat] ?? ucfirst($cat) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($run->compliance_score !== null)
                                        <span class="font-medium {{ $run->compliance_score >= 80 ? 'text-emerald-600' : ($run->compliance_score >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $run->compliance_score }}%</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('audit-runs.show', $run) }}" class="text-primary hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-themeMuted">No completed audits match the filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($runs->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $runs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
