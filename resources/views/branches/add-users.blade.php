@extends('layouts.app')

@section('title', 'Add Users to ' . $branch->name)

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Add Users to {{ $branch->name }}</h1>
            <a href="{{ route('branches.show', $branch) }}"
                class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            @if ($availableUsers->count() > 0)
                <form method="POST" action="{{ route('branches.assign-users', $branch) }}" class="space-y-6">
                    @csrf
                    <div class="mb-4">
                        <p class="text-sm font-medium text-themeBody">
                            Select users from your branch to assign to <strong
                                class="text-themeHeading">{{ $branch->name }}</strong>. Only users from your current branch are
                            available for transfer.
                        </p>
                    </div>
                    <div class="rounded-xl border border-themeBorder overflow-hidden">
                        <table class="min-w-full divide-y divide-themeBorder">
                            <thead class="bg-themeInput/80">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="select-all"
                                            class="rounded border-themeBorder text-primary focus:ring-primary/20">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Email</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Role</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Phone</th>
                                </tr>
                            </thead>
                            <tbody class="bg-themeCard divide-y divide-themeBorder">
                                @foreach ($availableUsers as $user)
                                    <tr class="hover:bg-themeInput/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                                class="user-checkbox rounded border-themeBorder text-primary focus:ring-primary/20">
                                        </td>
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
                                                $roleName = $user->roleModel?->name ?? ucfirst(str_replace('_', ' ', $user->role));
                                                $roleClass = $roleSlug === 'admin' || $roleSlug === 'super_admin' ? 'bg-violet-100 text-violet-800' : (in_array($roleSlug, ['head_branch_manager', 'regional_branch_manager'], true) ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody');
                                            @endphp
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $roleClass }}">
                                                {{ $roleName }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-themeBody">{{ $user->phone ?? '-' }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Assign Selected Users</span>
                        </button>
                        <a href="{{ route('branches.show', $branch) }}"
                            class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Cancel</span>
                        </a>
                    </div>
                </form>
            @else
                <div class="text-center py-10">
                    <svg class="mx-auto h-12 w-12 text-themeMuted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                    <p class="mt-2 text-sm font-medium text-themeMuted">No users available from your branch to assign</p>
                    <p class="mt-1 text-xs font-medium text-themeMuted">All users from your branch are already assigned to
                        this branch</p>
                    <a href="{{ route('branches.show', $branch) }}"
                        class="mt-4 inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Back to Branch
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if ($availableUsers->count() > 0)
        <script>
            // Select all functionality
            document.getElementById('select-all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.user-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Update select all when individual checkboxes change
            document.querySelectorAll('.user-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.user-checkbox');
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    document.getElementById('select-all').checked = allChecked;
                });
            });
        </script>
    @endif
@endsection

