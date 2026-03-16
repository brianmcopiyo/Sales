@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('team.index'),
            'label' => 'Back to Team & Access',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Users</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Manage user accounts</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('users.create'))
                    <button type="button" onclick="document.getElementById('import-users-modal').classList.remove('hidden')"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        <span>Import</span>
                    </button>
                    <a href="{{ route('users.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add User</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section -->
        @php
            $adminRole = $roles->firstWhere('slug', 'admin');
            $staffRoles = $roles->whereIn('slug', ['head_branch_manager', 'regional_branch_manager', 'staff']);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('users.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('role_id') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="users-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Users</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </a>
            <a href="{{ route('users.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('role_id') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="users-active">
                <div class="text-sm font-medium text-themeMuted mb-1">Active Users</div>
                <div class="text-2xl font-semibold text-emerald-600">{{ $stats['active'] }}</div>
            </a>
            @if($adminRole)
            <a href="{{ route('users.index', ['role_id' => $adminRole->id]) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('role_id') == $adminRole->id ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="users-admin">
                <div class="text-sm font-medium text-themeMuted mb-1">Admins</div>
                <div class="text-2xl font-semibold text-violet-600">{{ $stats['admins'] }}</div>
            </a>
            @endif
            @if($staffRoles->isNotEmpty())
            <a href="{{ route('users.index', ['role_id' => $staffRoles->first()->id]) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ $staffRoles->pluck('id')->contains(request('role_id')) ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="users-staff">
                <div class="text-sm font-medium text-themeMuted mb-1">Staff</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['staff'] }}</div>
            </a>
            @endif
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Name or email..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="w-48">
                    <label for="branch" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch" name="branch"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All branches</option>
                        @foreach ($branches ?? [] as $b)
                            <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-48">
                    <label for="role_id" class="block text-sm font-medium text-themeBody mb-2">Role</label>
                    <select id="role_id" name="role_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All roles</option>
                        @foreach ($roles ?? [] as $role)
                            <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Filter
                </button>
                @if (request()->hasAny(['search', 'branch', 'role_id']))
                    <a href="{{ route('users.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($users as $user)
                    <a href="{{ auth()->user()?->hasPermission('users.view') ? route('users.show', $user) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('users.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1 flex items-center gap-3">
                                <x-profile-picture :user="$user" size="sm" />
                                <div>
                                    <div class="text-sm font-semibold text-primary">{{ $user->name }}</div>
                                    <div class="text-xs text-themeBody truncate">{{ $user->email }}</div>
                                    <div class="text-xs text-themeMuted mt-0.5">{{ $user->branch ? $user->branch->name : '—' }}</div>
                                </div>
                            </div>
                            @php
                                $roleSlug = $user->roleModel?->slug ?? $user->role;
                                $roleLabel = $user->roleModel?->name ?? ucfirst(str_replace('_', ' ', $user->role));
                                $roleClass = $roleSlug === 'admin' || $roleSlug === 'super_admin' ? 'bg-violet-100 text-violet-800' : ($roleSlug === 'head_branch_manager' ? 'bg-sky-100 text-sky-800' : ($roleSlug === 'regional_branch_manager' ? 'bg-indigo-100 text-indigo-800' : ($roleSlug === 'staff' ? 'bg-themeHover text-themeBody' : 'bg-emerald-100 text-emerald-800')));
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium flex-shrink-0 {{ $roleClass }}">{{ $roleLabel }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No users found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Role</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Phone</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder" id="users-table-body">
                        @forelse($users as $user)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <x-profile-picture :user="$user" size="sm" />
                                        <div class="text-sm font-medium text-themeHeading">{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $roleSlug = $user->roleModel?->slug ?? $user->role;
                                        $roleLabel = $user->roleModel?->name ?? ucfirst(str_replace('_', ' ', $user->role));
                                        $roleClass =
                                            $roleSlug === 'admin' || $roleSlug === 'super_admin'
                                                ? 'bg-violet-100 text-violet-800'
                                                : ($roleSlug === 'head_branch_manager'
                                                    ? 'bg-sky-100 text-sky-800'
                                                    : ($roleSlug === 'regional_branch_manager'
                                                        ? 'bg-indigo-100 text-indigo-800'
                                                        : ($roleSlug === 'staff'
                                                            ? 'bg-themeHover text-themeBody'
                                                            : 'bg-emerald-100 text-emerald-800')));
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $roleClass }}">{{ $roleLabel }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $user->branch ? $user->branch->name : '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $user->phone ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button @click="open = !open" x-ref="button"
                                            class="text-themeBody hover:text-themeHeading focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                                </path>
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute right-0 top-full z-[9999] mt-2 w-48 bg-themeCard rounded-xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                                            style="display: none;">
                                            <div class="py-1">
                                                @if (auth()->user()?->hasPermission('users.view'))
                                                    <a href="{{ route('users.show', $user) }}"
                                                        class="block px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                                            </path>
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                            </path>
                                                        </svg>
                                                        <span>View</span>
                                                    </a>
                                                @endif
                                                @if ($user->id !== auth()->id())
                                                    @if (auth()->user()?->hasPermission('users.update'))
                                                        <a href="{{ route('users.edit', $user) }}"
                                                            class="block px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                                </path>
                                                            </svg>
                                                            <span>Edit</span>
                                                        </a>
                                                    @endif
                                                    @if (auth()->user()?->hasPermission('users.manage-field-agents') && !$user->fieldAgentProfile && $user->role !== 'customer')
                                                        <form action="{{ route('users.make-field-agent', $user) }}"
                                                            method="POST" class="block" onsubmit="return confirm('Convert this user to a field agent?');">
                                                            @csrf
                                                            <button type="submit"
                                                                class="w-full text-left px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeInput transition flex items-center space-x-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                                </svg>
                                                                <span>Convert to field agent</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if (auth()->user()?->hasPermission('users.delete'))
                                                        <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                            onsubmit="return confirm('Are you sure?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="w-full text-left px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition flex items-center space-x-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                    </path>
                                                                </svg>
                                                                <span>Delete</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-themeMuted font-medium">No users
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>


        <!-- Import users modal -->
        @if (auth()->user()?->hasPermission('users.create'))
            <div id="import-users-modal"
                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="document.getElementById('import-users-modal').classList.add('hidden')">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Import users</h2>
                        <button type="button"
                            onclick="document.getElementById('import-users-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('users.import.submit') }}" enctype="multipart/form-data"
                        class="space-y-4">
                        @csrf
                        <div>
                            <label for="import_users_file" class="block text-sm font-medium text-themeBody mb-2">CSV or
                                Excel file *</label>
                            <input type="file" name="file" id="import_users_file" accept=".csv,.xlsx,.xls"
                                required
                                class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                            <p class="mt-1 text-xs text-themeMuted">
                                <a href="{{ route('users.import.sample') }}"
                                    class="text-primary hover:underline font-medium">Download sample CSV</a> — columns:
                                name, email, role, phone, branch.
                            </p>
                        </div>
                        <div class="flex items-center space-x-3 pt-4">
                            <button type="submit"
                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <span>Import</span>
                            </button>
                            <a href="{{ route('users.import') }}"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2 text-center">
                                Full import page
                            </a>
                            <button type="button"
                                onclick="document.getElementById('import-users-modal').classList.add('hidden')"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
