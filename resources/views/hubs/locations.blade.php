@extends('layouts.app')

@section('title', 'Locations')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Locations</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Branches and regions</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('branches.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Branches</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['branches_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['branches_active'] }} active</div>
            </a>
            <a href="{{ route('branches.index', ['is_active' => '1']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Active Branches</div>
                <div class="text-2xl font-semibold text-emerald-600 mt-1">{{ $stats['branches_active'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">of {{ $stats['branches_total'] }} total</div>
            </a>
            <a href="{{ route('regions.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Regions</div>
                <div class="text-2xl font-semibold text-violet-600 mt-1">{{ $stats['regions_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['regions_active'] }} active</div>
            </a>
            <a href="{{ route('regions.index', ['is_active' => '1']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Active Regions</div>
                <div class="text-2xl font-semibold text-emerald-600 mt-1">{{ $stats['regions_active'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">of {{ $stats['regions_total'] }} total</div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if (auth()->user()?->hasPermission('branches.view'))
                @include('hubs._hub-card', [
                    'href' => route('branches.index'),
                    'title' => 'Branches',
                    'description' => 'Branch locations and management',
                    'icon' =>
                        'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ])
            @endif
            @if (auth()->user()?->hasPermission('regions.view'))
                @include('hubs._hub-card', [
                    'href' => route('regions.index'),
                    'title' => 'Regions',
                    'description' => 'Regional grouping of branches',
                    'icon' =>
                        'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ])
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @if (auth()->user()?->hasPermission('branches.view') && $recentBranches->isNotEmpty())
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Branches</h2>
                        <a href="{{ route('branches.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                    </div>
                    <ul class="divide-y divide-themeBorder" id="branches-list">
                        @foreach ($recentBranches as $branch)
                            <li class="filterable-row px-6 py-3 hover:bg-themeInput/50 transition"
                                data-is-active="{{ $branch->is_active ? 'true' : 'false' }}" data-filter-group="branches">
                                <a href="{{ route('branches.show', $branch) }}"
                                    class="flex justify-between items-center gap-2">
                                    <span class="text-sm font-medium text-themeHeading truncate">{{ $branch->name }}</span>
                                    <span
                                        class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                                </a>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $branch->region?->name ?? '-' }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (auth()->user()?->hasPermission('regions.view') && $recentRegions->isNotEmpty())
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Regions</h2>
                        <a href="{{ route('regions.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                    </div>
                    <ul class="divide-y divide-themeBorder" id="regions-list">
                        @foreach ($recentRegions as $region)
                            <li class="px-6 py-3 hover:bg-themeInput/50 transition">
                                <a href="{{ route('regions.show', $region) }}"
                                    class="flex justify-between items-center gap-2">
                                    <span class="text-sm font-medium text-themeHeading truncate">{{ $region->name }}</span>
                                    <span
                                        class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $region->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $region->is_active ? 'Active' : 'Inactive' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

@endsection
