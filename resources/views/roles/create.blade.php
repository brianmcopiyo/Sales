@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Role</h1>
            <a href="{{ route('roles.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <form method="POST" action="{{ route('roles.store') }}" class="space-y-6">
            @csrf

            <!-- Role Information Card -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                <h2 class="text-xl font-semibold text-primary mb-6">Role Information</h2>

                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-themeBody font-medium mb-2">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('name')
                            <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-themeBody font-medium mb-2">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_active" value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}
                                class="rounded border-themeBorder text-primary focus:ring-primary">
                            <span class="text-themeBody font-medium">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Permissions Section - Separated Card -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl text-primary font-light">Permissions</h2>
                        <p class="text-sm text-themeMuted font-light mt-1">Select the permissions you want this role to have
                        </p>
                    </div>
                    @if ($permissions->isNotEmpty())
                        <div class="flex space-x-2">
                            <button type="button" onclick="selectAllPermissions()"
                                class="text-sm text-primary hover:text-[#005a61] font-light px-3 py-1 border border-[#006F78] rounded hover:bg-primary hover:text-white transition">
                                Select All
                            </button>
                            <button type="button" onclick="deselectAllPermissions()"
                                class="text-sm text-themeBody hover:text-themeHeading font-light px-3 py-1 border border-themeBorder rounded hover:bg-themeHover transition">
                                Deselect All
                            </button>
                        </div>
                    @endif
                </div>

                @if ($permissions->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($permissions->groupBy('module') as $module => $modulePermissions)
                            <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 hover:bg-themeInput transition">
                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-themeBorder">
                                    <h4 class="text-sm font-semibold text-themeHeading">{{ $module ?: 'General' }}</h4>
                                    <span
                                        class="text-xs font-medium text-themeMuted bg-themeCard px-2 py-1 rounded-lg">{{ $modulePermissions->count() }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach ($modulePermissions as $permission)
                                        <label
                                            class="flex flex-col items-start space-y-1 p-2 hover:bg-white rounded-lg cursor-pointer transition">
                                            <div class="flex items-start space-x-2 w-full">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                    {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                                    class="mt-1 rounded border-themeBorder text-primary focus:ring-primary">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-medium text-themeHeading leading-tight">
                                                        {{ $permission->name }}</div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-themeInput border border-themeBorder rounded-lg p-8 text-center">
                        <svg class="w-16 h-16 text-themeMuted mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                        <p class="text-sm text-themeMuted font-light">No permissions available in the system.</p>
                        <p class="text-xs text-themeMuted font-light mt-1">Permissions need to be created in the database
                            before they can be assigned to roles.</p>
                    </div>
                @endif
            </div>

            <!-- Action Buttons - At Bottom -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('roles.index') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        <span>Cancel</span>
                    </a>
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Create Role</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function selectAllPermissions() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function deselectAllPermissions() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
@endsection

