@extends('layouts.app')

@section('title', $role->name)

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $role->name }}</h1>
            <div class="flex space-x-2">
                @if (!$role->is_protected)
                    <a href="{{ route('roles.edit', $role) }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span>Edit</span>
                    </a>
                @endif
                <a href="{{ route('roles.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <h2 class="text-xl font-semibold text-primary mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                            <div class="text-lg font-medium text-themeHeading">{{ $role->name }}</div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            <div class="flex flex-col gap-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $role->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if ($role->is_protected)
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium bg-amber-100 text-amber-800">
                                        Protected
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($role->description)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Description</div>
                                <div class="font-medium text-themeHeading">{{ $role->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Permissions Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <h2 class="text-xl font-semibold text-primary mb-4">Permissions ({{ $role->permissions->count() }})
                    </h2>
                    @if ($role->permissions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($role->permissions->groupBy('module') as $module => $modulePermissions)
                                <div class="border border-themeBorder rounded-xl p-4">
                                    <h4 class="text-sm font-semibold text-themeHeading mb-3">{{ $module ?: 'General' }}</h4>
                                    <ul class="space-y-2">
                                        @foreach ($modulePermissions as $permission)
                                            <li class="text-sm font-medium text-themeBody flex items-start">
                                                <svg class="w-4 h-4 text-emerald-500 mr-2 mt-0.5 flex-shrink-0"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <div>
                                                    <div>{{ $permission->name }}</div>
                                                    @if ($permission->description)
                                                        <div class="text-xs font-medium text-themeMuted">
                                                            {{ $permission->description }}</div>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="font-medium text-themeMuted">No permissions assigned to this role.</p>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <h2 class="text-xl text-primary font-light mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Total Permissions</div>
                            <div class="text-2xl text-primary font-light">{{ $role->permissions->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Users with this Role</div>
                            <div class="text-2xl text-primary font-light">{{ $role->users->count() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Users Card -->
                @if ($role->users->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                        <h2 class="text-xl font-semibold text-primary mb-4">Users ({{ $role->users->count() }})</h2>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach ($role->users as $user)
                                <div class="border-l-4 border-[#006F78] pl-4 py-2">
                                    <div class="flex items-center gap-3">
                                        <x-profile-picture :user="$user" size="sm" />
                                        <div>
                                            <div class="text-sm font-medium text-themeHeading">{{ $user->name }}</div>
                                            <div class="text-xs font-medium text-themeMuted">{{ $user->email }}</div>
                                            @if ($user->branch)
                                                <div class="text-xs font-medium text-themeMuted">{{ $user->branch->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

