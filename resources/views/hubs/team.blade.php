@extends('layouts.app')

@section('title', 'Team & Access')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Team & Access</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Users, roles, and field agents</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <a href="{{ route('users.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Users</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['users_total'] }}</div>
            </a>
            <a href="{{ route('roles.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Roles</div>
                <div class="text-2xl font-semibold text-violet-600 mt-1">{{ $stats['roles_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['roles_active'] }} active</div>
            </a>
            <a href="{{ route('field-agents.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Field Agents</div>
                <div class="text-2xl font-semibold text-amber-600 mt-1">{{ $stats['field_agents_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['field_agents_active'] }} active</div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (auth()->user()?->hasPermission('users.view'))
                @include('hubs._hub-card', [
                    'href' => route('users.index'),
                    'title' => 'Users',
                    'description' => 'User accounts and access',
                    'icon' =>
                        'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                ])
            @endif
            @if (auth()->user()?->hasPermission('roles.view'))
                @include('hubs._hub-card', [
                    'href' => route('roles.index'),
                    'title' => 'Roles & Permissions',
                    'description' => 'Roles and permission assignment',
                    'icon' =>
                        'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                ])
            @endif
            @if (auth()->user()?->hasPermission('field-agents.view'))
                @include('hubs._hub-card', [
                    'href' => route('field-agents.index'),
                    'title' => 'Field Agents',
                    'description' => 'Field agents and commission tracking',
                    'icon' =>
                        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                ])
            @endif
        </div>

        @if (auth()->user()?->hasPermission('users.view') && $recentUsers->isNotEmpty())
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Users</h2>
                    <a href="{{ route('users.index') }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                </div>
                <ul class="divide-y divide-themeBorder" id="users-list">
                    @foreach ($recentUsers as $u)
                        <li class="px-6 py-3 hover:bg-themeInput/50 transition">
                            <a href="{{ route('users.show', $u) }}" class="flex justify-between items-center gap-2">
                                <span class="text-sm font-medium text-themeHeading truncate">{{ $u->name }}</span>
                            </a>
                            <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $u->email }} ·
                                {{ $u->branch?->name ?? '-' }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

@endsection
