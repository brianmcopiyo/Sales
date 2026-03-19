@extends('layouts.app')

@section('title', 'Distribution Dashboard')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('dashboard'), 'label' => 'Back to Dashboard'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Distribution Dashboard</h1>
            @if ($branchHasDescendants ?? false)
                <div class="flex rounded-lg border border-themeBorder overflow-hidden">
                    <a href="{{ route('distribution.dashboard', ['include_descendants' => '0']) }}"
                        class="px-3 py-2 text-sm font-medium transition {{ !$includeDescendants ? 'bg-primary text-white' : 'bg-themeInput/50 text-themeBody hover:bg-themeHover' }}">This branch only</a>
                    <a href="{{ route('distribution.dashboard', ['include_descendants' => '1']) }}"
                        class="px-3 py-2 text-sm font-medium transition {{ $includeDescendants ? 'bg-primary text-white' : 'bg-themeInput/50 text-themeBody hover:bg-themeHover' }}">Branch + sub-branches</a>
                </div>
            @endif
        </div>

        <p class="text-themeMuted text-sm">Outlets, check-ins and coverage for your scope.</p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Outlets</div>
                <div class="text-2xl font-semibold text-themeHeading mt-1">{{ number_format($outletsCount) }}</div>
                <a href="{{ route('outlets.index') }}" class="text-sm text-primary font-medium hover:underline mt-2 inline-block">View outlets →</a>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Check-ins today</div>
                <div class="text-2xl font-semibold text-emerald-600 mt-1">{{ number_format($checkInsToday) }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Check-ins this week</div>
                <div class="text-2xl font-semibold text-themeHeading mt-1">{{ number_format($checkInsThisWeek) }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-themeMuted text-xs font-medium uppercase tracking-wider">Coverage (this week)</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $coveragePercent }}%</div>
                <div class="text-xs text-themeMuted mt-0.5">{{ number_format($outletsVisitedThisWeek) }} of {{ number_format($outletsCount) }} outlets visited</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-4">
            <a href="{{ route('check-ins.index') }}"
                class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                View check-ins list
            </a>
            @if (auth()->user()?->hasPermission('checkins.create'))
                <a href="{{ route('check-ins.create') }}"
                    class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                    New check-in
                </a>
            @endif
        </div>
    </div>
@endsection
