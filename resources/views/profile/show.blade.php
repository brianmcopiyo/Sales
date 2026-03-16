@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="w-full">
        <div class="mb-6">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">My Profile</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Picture Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Profile Picture</h2>
                    <div class="flex items-center space-x-6">
                        <label for="imageInput" class="relative group cursor-pointer block" style="cursor: pointer;">
                            @if ($user->profile_picture)
                                <img src="{{ $user->profile_picture_url }}" alt="Profile Picture"
                                    class="w-32 h-32 rounded-full object-cover border-4 border-themeBorder transition-opacity group-hover:opacity-75">
                            @else
                                <div
                                    class="w-32 h-32 rounded-full bg-primary/10 flex items-center justify-center border-4 border-themeBorder transition-opacity group-hover:opacity-75">
                                    <svg class="w-16 h-16 text-primary/50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                            @endif
                            <!-- Overlay on hover -->
                            <div
                                class="absolute inset-0 rounded-full bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center pointer-events-none">
                                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        </label>
                        <div class="flex flex-col space-y-2">
                            <p class="text-sm text-themeMuted font-light">Click the image to
                                {{ $user->profile_picture ? 'change' : 'upload' }} your profile picture</p>
                            @if ($user->profile_picture)
                                <form method="POST" action="{{ route('profile.picture.delete') }}" class="inline"
                                    onsubmit="return confirm('Are you sure you want to delete your profile picture?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 bg-red-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-red-600 transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                        <span>Delete Picture</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Profile Information Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Profile Information</h2>
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-themeBody mb-1">Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-themeBody mb-1">Email</label>
                                <input type="email" id="email" value="{{ $user->email }}" disabled
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeMuted">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-themeBody mb-1">Phone</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-themeBody mb-1">Role</label>
                                <input type="text" id="role"
                                    value="{{ $user->roleModel?->name ?? ucfirst(str_replace('_', ' ', $user->role)) }}" disabled
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeMuted">
                            </div>

                        </div>

                        <div class="mt-6 flex items-center space-x-4">
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Update Profile</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Branch Information Card -->
                @if ($user->branch)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">My Branch</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Branch Name</div>
                                <div class="text-base font-medium text-themeHeading">{{ $user->branch->name }}</div>
                            </div>
                            @if ($user->branch->code)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Branch Code</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $user->branch->code }}</div>
                                </div>
                            @endif
                            @if ($user->branch->region)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Region</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $user->branch->region->name }}
                                    </div>
                                </div>
                            @endif
                            @if ($user->branch->address)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Address</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $user->branch->address }}</div>
                                </div>
                            @endif
                            @if ($user->branch->phone)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Phone</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $user->branch->phone }}</div>
                                </div>
                            @endif
                            @if ($user->branch->email)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $user->branch->email }}</div>
                                </div>
                            @endif
                        </div>
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <a href="{{ route('branches.show', $user->branch->id) }}"
                                class="text-sm font-medium text-primary hover:text-primary-dark transition inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                                <span>View Branch Details</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Theme Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-1">Theme</h2>
                    <p class="text-sm text-themeMuted mb-5">Choose the accent color for buttons, links, and highlights.</p>
                    @php $currentTheme = $user->getThemeKey(); @endphp
                    <div class="flex flex-wrap gap-3">
                        @foreach ($themes ?? [] as $key => $theme)
                            <form method="POST" action="{{ route('profile.theme.update') }}" class="inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="theme" value="{{ $key }}">
                                <button type="submit"
                                    class="group relative block w-20 h-20 rounded-xl border-2 overflow-hidden transition shadow-sm hover:scale-105 hover:shadow-md {{ $currentTheme === $key ? 'border-primary ring-2 ring-primary/20' : 'border-themeBorder hover:border-themeMuted' }}"
                                    style="{{ $key === 'dark' ? 'background: ' . ($theme['bg_page'] ?? '#0f172a') . ';' : 'background: linear-gradient(135deg, ' . ($theme['primary'] ?? '#006F78') . ' 0%, ' . ($theme['primary_dark'] ?? ($theme['primary'] ?? '#005a62')) . ' 100%);' }}"
                                    title="{{ $theme['label'] }}">
                                    <span class="absolute inset-0 flex items-end justify-center pb-1">
                                        <span
                                            class="text-xs font-medium drop-shadow-md {{ $key === 'dark' ? 'text-slate-200' : 'text-white' }}">{{ $theme['label'] }}</span>
                                    </span>
                                    @if ($currentTheme === $key)
                                        <span
                                            class="absolute top-1 right-1 w-5 h-5 rounded-full bg-themeCard/90 flex items-center justify-center shadow">
                                            <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>

                <!-- Background Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-1">Background</h2>
                    <p class="text-sm text-themeMuted mb-5">Choose how your dashboard looks. Click a sample to apply it.
                    </p>

                    @php
                        $bgType = $user->dashboard_background_type ?? null;
                        $bgValue = $user->dashboard_background_value ?? null;
                    @endphp

                    <!-- Option: Default / None -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-themeMuted uppercase tracking-wider mb-3">Default</p>
                        <form method="POST" action="{{ route('profile.dashboard-background.update') }}" class="inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="dashboard_background_type" value="">
                            <input type="hidden" name="dashboard_background_value" value="">
                            <button type="submit"
                                class="group relative block w-full max-w-[200px] h-20 rounded-xl border-2 overflow-hidden transition bg-themePage {{ !$bgType ? 'border-primary ring-2 ring-primary/20' : 'border-themeBorder hover:border-themeHover' }}">
                                <span
                                    class="absolute inset-0 flex items-center justify-center text-sm font-medium text-themeMuted group-hover:text-themeHeading">Default</span>
                                @if (!$bgType)
                                    <span
                                        class="absolute top-2 right-2 w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                @endif
                            </button>
                        </form>
                    </div>

                    <!-- Option 1: Color -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-themeMuted uppercase tracking-wider mb-3">Color</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($dashboardBackgrounds['color'] ?? [] as $key => $preset)
                                <form method="POST" action="{{ route('profile.dashboard-background.update') }}"
                                    class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="dashboard_background_type" value="color">
                                    <input type="hidden" name="dashboard_background_value" value="{{ $key }}">
                                    <button type="submit"
                                        class="group relative block w-20 h-20 rounded-xl border-2 overflow-hidden transition shadow-sm hover:scale-105 hover:shadow-md {{ $bgType === 'color' && $bgValue === $key ? 'border-primary ring-2 ring-primary/20' : 'border-themeBorder hover:border-themeHover' }}"
                                        style="background: {{ strpos($preset['sample'], 'gradient') !== false ? $preset['sample'] : $preset['sample'] }};"
                                        title="{{ $preset['label'] }}">
                                        <span class="absolute inset-0 flex items-end justify-center pb-1">
                                            <span
                                                class="text-xs font-medium text-white drop-shadow-md">{{ $preset['label'] }}</span>
                                        </span>
                                        @if ($bgType === 'color' && $bgValue === $key)
                                            <span
                                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-themeCard/90 flex items-center justify-center shadow">
                                                <svg class="w-3 h-3 text-primary" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <!-- Option 2: Pattern -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-themeMuted uppercase tracking-wider mb-3">Pattern</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($dashboardBackgrounds['pattern'] ?? [] as $key => $preset)
                                <form method="POST" action="{{ route('profile.dashboard-background.update') }}"
                                    class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="dashboard_background_type" value="pattern">
                                    <input type="hidden" name="dashboard_background_value" value="{{ $key }}">
                                    <button type="submit"
                                        class="group relative block w-20 h-20 rounded-xl border-2 overflow-hidden transition shadow-sm hover:scale-105 hover:shadow-md {{ $bgType === 'pattern' && $bgValue === $key ? 'border-primary ring-2 ring-primary/20' : 'border-themeBorder hover:border-themeHover' }}"
                                        style="{{ $preset['css'] }}" title="{{ $preset['label'] }}">
                                        <span class="absolute inset-0 flex items-end justify-center pb-1">
                                            <span
                                                class="text-xs font-medium text-themeBody drop-shadow-sm bg-themeCard/70 px-2 py-0.5 rounded">{{ $preset['label'] }}</span>
                                        </span>
                                        @if ($bgType === 'pattern' && $bgValue === $key)
                                            <span
                                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-primary flex items-center justify-center shadow">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <!-- Option 3: Image -->
                    <div>
                        <p class="text-xs font-semibold text-themeMuted uppercase tracking-wider mb-3">Image</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($dashboardBackgrounds['image'] ?? [] as $key => $preset)
                                <form method="POST" action="{{ route('profile.dashboard-background.update') }}"
                                    class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="dashboard_background_type" value="image">
                                    <input type="hidden" name="dashboard_background_value" value="{{ $key }}">
                                    <button type="submit"
                                        class="group relative block w-24 h-20 rounded-xl border-2 overflow-hidden transition shadow-sm hover:scale-105 hover:shadow-md {{ $bgType === 'image' && $bgValue === $key ? 'border-primary ring-2 ring-primary/20' : 'border-themeBorder hover:border-themeHover' }}"
                                        style="background-image: url('{{ $preset['url'] }}'); background-size: cover; background-position: center;"
                                        title="{{ $preset['label'] }}">
                                        <span
                                            class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition flex items-end justify-center pb-1">
                                            <span
                                                class="text-xs font-medium text-white drop-shadow-md">{{ $preset['label'] }}</span>
                                        </span>
                                        @if ($bgType === 'image' && $bgValue === $key)
                                            <span
                                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-primary flex items-center justify-center shadow">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                            {{-- Custom / uploaded image --}}
                            @php
                                $customPath = $user->dashboard_background_custom_path ?? null;
                                $customUrl = $customPath ? \Illuminate\Support\Facades\Storage::url($customPath) : null;
                                $isCustomActive = $bgType === 'image' && $bgValue === 'custom';
                            @endphp
                            <div class="inline-block">
                                <form method="POST" action="{{ route('profile.dashboard-background.update') }}"
                                    enctype="multipart/form-data" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="dashboard_background_type" value="image">
                                    <input type="hidden" name="dashboard_background_value" value="custom">
                                    <input type="file" name="dashboard_background_image"
                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="hidden"
                                        id="dashboard-background-custom-file" onchange="this.form.submit()">
                                    <button type="button"
                                        onclick="document.getElementById('dashboard-background-custom-file').click()"
                                        class="group relative block w-24 h-20 rounded-xl border-2 overflow-hidden transition shadow-sm hover:scale-105 hover:shadow-md {{ $isCustomActive ? 'border-primary ring-2 ring-primary/20' : 'border-themeBorder hover:border-themeHover' }}"
                                        style="{{ $customUrl ? "background-image: url('" . e($customUrl) . "'); background-size: cover; background-position: center;" : 'background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);' }}"
                                        title="Upload your own image">
                                        <span
                                            class="absolute inset-0 flex flex-col items-center justify-center gap-0.5 {{ $customUrl ? 'bg-black/30 group-hover:bg-black/20' : 'bg-transparent' }} transition">
                                            <svg class="w-6 h-6 text-themeMuted {{ $customUrl ? 'text-white drop-shadow' : '' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            <span
                                                class="text-xs font-medium {{ $customUrl ? 'text-white drop-shadow-md' : 'text-themeMuted' }}">{{ $customUrl ? 'Change' : 'Upload' }}</span>
                                        </span>
                                        @if ($isCustomActive)
                                            <span
                                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-primary flex items-center justify-center shadow">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                </form>
                                @if ($customUrl)
                                    <p class="text-xs text-themeMuted mt-1">Your image</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Change Password</h2>
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-themeBody mb-1">New
                                    Password</label>
                                <input type="password" name="password" id="password"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeBody placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('password') border-red-300 @enderror">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation"
                                    class="block text-sm font-medium text-themeBody mb-1">Confirm New Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeBody placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Update Password</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Activities Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Activities</h2>
                        <a href="{{ route('activity-logs.index', ['user_id' => $user->id]) }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark transition">
                            View All
                        </a>
                    </div>
                    @if ($activityLogs->count() > 0)
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach ($activityLogs as $log)
                                <div class="border-l-4 border-primary pl-4 py-2 rounded-r-lg bg-themeInput/50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-themeHeading">{{ $log->description }}
                                            </div>
                                            <div class="text-xs font-medium text-themeMuted mt-1">
                                                {{ $log->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                        @if ($log->model)
                                            <div class="ml-4">
                                                @php
                                                    $routeName = match ($log->model_type) {
                                                        'App\Models\Sale' => 'sales.show',
                                                        'App\Models\StockTransfer' => 'stock-transfers.show',
                                                        'App\Models\Ticket' => 'tickets.show',
                                                        'App\Models\Customer' => 'customers.show',
                                                        'App\Models\Product' => 'products.show',
                                                        'App\Models\Device' => 'devices.show',
                                                        'App\Models\User' => 'users.show',
                                                        default => null,
                                                    };
                                                @endphp
                                                @if ($routeName)
                                                    <a href="{{ route($routeName, $log->model_id) }}"
                                                        class="text-xs font-medium text-primary hover:text-primary-dark transition">
                                                        View
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-themeMuted font-medium">
                            No activities found.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Links Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        <a href="{{ route('dashboard') }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                </path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                        @if ($user->branch)
                            <a href="{{ route('branches.show', $user->branch->id) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                                <span>My Branch</span>
                            </a>
                        @endif

                        @if ($isFieldAgent)
                            <a href="{{ route('commission-disbursements.index') }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v2m-7-6a7 7 0 1114 0 7 7 0 01-14 0z">
                                    </path>
                                </svg>
                                <span>My Commissions</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Stats Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Account Stats</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Member Since</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $user->created_at->format('M Y') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Sales</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $user->sales()->count() }}</div>
                        </div>
                        @if ($user->isCustomer())
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">My Tickets</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $user->tickets()->count() }}
                                </div>
                            </div>
                        @endif
                        @if ($isFieldAgent && $commissionStats)
                            <div class="pt-4 border-t border-themeBorder">
                                <div class="text-sm font-medium text-themeMuted mb-2">Commission</div>
                                <div class="space-y-2">
                                    <div>
                                        <div class="text-xs font-medium text-themeMuted">Total Earned</div>
                                        <div class="text-base font-semibold text-primary">TSh
                                            {{ number_format($commissionStats['total_earned'], 2) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-themeMuted">Available Balance</div>
                                        <div class="text-base font-semibold text-amber-600">TSh
                                            {{ number_format($commissionStats['available_balance'], 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Profile Picture Crop Modal -->
    <div id="cropModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        style="overflow: hidden;">
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-4xl w-full"
            style="max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <h3 class="text-xl font-semibold text-primary">Crop Profile Picture</h3>
                <button type="button" onclick="closeCropModal()"
                    class="text-themeMuted hover:text-themeHeading transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-hidden flex flex-col">
                <div class="bg-themeInput rounded-xl p-4 flex-1 flex items-center justify-center"
                    style="min-height: 0; overflow: hidden;">
                    <div id="cropContainer"
                        style="width: 100%; height: 100%; max-width: 600px; max-height: 600px; position: relative;">
                        <img id="cropImage" src="" alt="Crop"
                            style="display: block; max-width: 100%; max-height: 100%;">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-4 flex-shrink-0">
                    <button type="button" onclick="closeCropModal()"
                        class="px-4 py-2 bg-themeHover text-themeBody rounded-xl font-medium hover:bg-themeBorder transition">
                        Cancel
                    </button>
                    <button type="button" onclick="cropAndSave()"
                        class="px-4 py-2 bg-primary text-white rounded-xl font-medium hover:bg-[#005a61] transition">
                        Save Picture
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden file input -->
    <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageSelect(event)">
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <style>
        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }

        /* Crop Container */
        #cropContainer {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        /* Cropper Container - Hide original image, show only canvas */
        .cropper-container {
            direction: ltr;
            position: relative;
            overflow: hidden;
            width: 100% !important;
            height: 100% !important;
        }

        /* Hide the original image element - Cropper.js uses canvas */
        .cropper-container>img {
            display: none !important;
            visibility: hidden !important;
        }

        /* Ensure canvas is visible */
        .cropper-container .cropper-canvas {
            display: block !important;
        }

        /* Crop box styling - Square with visible border */
        .cropper-crop-box {
            border: 2px solid #006F78 !important;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5) !important;
        }

        /* View box - Square outline */
        .cropper-view-box {
            outline: 2px solid #006F78 !important;
            outline-offset: -2px;
        }

        /* Grid lines - Rule of thirds */
        .cropper-line {
            background-color: rgba(0, 111, 120, 0.4) !important;
        }

        .cropper-line.line-h {
            height: 1px !important;
        }

        .cropper-line.line-v {
            width: 1px !important;
        }

        /* Dashed guides */
        .cropper-dashed {
            border-color: rgba(0, 111, 120, 0.5) !important;
            border-style: dashed !important;
            border-width: 1px !important;
        }

        /* Corner handles */
        .cropper-point {
            background-color: #006F78 !important;
            width: 10px !important;
            height: 10px !important;
            border: 2px solid #ffffff !important;
            border-radius: 50% !important;
        }

        /* Modal overlay */
        .cropper-modal {
            background-color: rgba(0, 0, 0, 0.5) !important;
        }

        /* Custom grid overlay - Rule of thirds */
        .crop-grid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 10;
        }

        .crop-grid-line {
            position: absolute;
            background-color: rgba(0, 111, 120, 0.7);
            z-index: 10;
        }

        .crop-grid-line.vertical {
            width: 2px;
            top: 0;
            bottom: 0;
        }

        .crop-grid-line.horizontal {
            height: 2px;
            left: 0;
            right: 0;
        }

        .crop-grid-line.line-v1 {
            left: 33.333%;
        }

        .crop-grid-line.line-v2 {
            left: 66.666%;
        }

        .crop-grid-line.line-h1 {
            top: 33.333%;
        }

        .crop-grid-line.line-h2 {
            top: 66.666%;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <script>
        (function() {
            'use strict';

            let cropper = null;
            const imageInput = document.getElementById('imageInput');
            const cropImage = document.getElementById('cropImage');
            const cropModal = document.getElementById('cropModal');
            const cropContainer = document.getElementById('cropContainer');

            // Open crop modal by triggering file input
            window.openCropModal = function() {
                if (imageInput) {
                    imageInput.click();
                }
            };

            // Close crop modal
            window.closeCropModal = function() {
                if (cropModal) {
                    cropModal.classList.add('hidden');
                    document.body.classList.remove('modal-open');
                }
                if (cropper) {
                    // Remove grid overlay
                    const grid = cropper.cropper?.querySelector('.crop-grid-overlay');
                    if (grid) {
                        grid.remove();
                    }
                    cropper.destroy();
                    cropper = null;
                }
                if (cropImage) {
                    cropImage.src = '';
                    cropImage.onload = null;
                }
                if (imageInput) {
                    imageInput.value = '';
                }
            };

            // Add grid overlay to crop box
            function addGridOverlay() {
                if (!cropper || !cropper.cropper) {
                    setTimeout(addGridOverlay, 50);
                    return;
                }

                const viewBox = cropper.cropper.querySelector('.cropper-view-box');
                if (!viewBox) {
                    setTimeout(addGridOverlay, 50);
                    return;
                }

                // Remove existing grid
                const existingGrid = viewBox.querySelector('.crop-grid-overlay');
                if (existingGrid) {
                    existingGrid.remove();
                }

                // Create grid container
                const grid = document.createElement('div');
                grid.className = 'crop-grid-overlay';

                // Create 4 grid lines (2 vertical, 2 horizontal)
                const lines = [{
                        class: 'crop-grid-line vertical line-v1',
                        style: 'left: 33.333%;'
                    },
                    {
                        class: 'crop-grid-line vertical line-v2',
                        style: 'left: 66.666%;'
                    },
                    {
                        class: 'crop-grid-line horizontal line-h1',
                        style: 'top: 33.333%;'
                    },
                    {
                        class: 'crop-grid-line horizontal line-h2',
                        style: 'top: 66.666%;'
                    }
                ];

                lines.forEach(function(lineConfig) {
                    const line = document.createElement('div');
                    line.className = lineConfig.class;
                    line.style.cssText = lineConfig.style;
                    grid.appendChild(line);
                });

                viewBox.appendChild(grid);
            }

            // Handle image file selection
            window.handleImageSelect = function(event) {
                const file = event.target.files[0];
                if (!file || !file.type.match('image.*')) {
                    if (file && !file.type.match('image.*')) {
                        alert('Please select an image file.');
                    }
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Destroy existing cropper
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }

                    // Set image source
                    cropImage.src = e.target.result;

                    // Show modal and prevent body scroll
                    cropModal.classList.remove('hidden');
                    document.body.classList.add('modal-open');

                    // Initialize cropper when image loads
                    cropImage.onload = function() {
                        // Small delay to ensure DOM is ready
                        setTimeout(function() {
                            cropper = new Cropper(cropImage, {
                                aspectRatio: 1, // Strict 1:1 square
                                viewMode: 1, // Crop box must be within canvas
                                dragMode: 'move',
                                autoCropArea: 0.8,
                                restore: false,
                                guides: true, // Show built-in guides
                                center: true,
                                highlight: true,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                                minCropBoxWidth: 100,
                                minCropBoxHeight: 100,
                                background: true,
                                modal: true,
                                responsive: true,
                                scalable: true,
                                zoomable: true,
                                rotatable: false,
                                ready: function() {
                                    // Ensure square aspect ratio
                                    this.cropper.setAspectRatio(1);

                                    // Hide original image element
                                    const img = this.cropper.image;
                                    if (img) {
                                        img.style.display = 'none';
                                        img.style.visibility = 'hidden';
                                    }

                                    // Add custom grid overlay
                                    setTimeout(addGridOverlay, 200);
                                },
                                crop: function() {
                                    // Maintain square aspect ratio
                                    this.cropper.setAspectRatio(1);
                                },
                                cropmove: function() {
                                    // Maintain square aspect ratio during move
                                    this.cropper.setAspectRatio(1);
                                },
                                cropend: function() {
                                    // Maintain square aspect ratio after resize
                                    this.cropper.setAspectRatio(1);
                                }
                            });
                        }, 100);
                    };
                };

                reader.readAsDataURL(file);
            };

            // Crop and save image
            window.cropAndSave = function() {
                if (!cropper) {
                    alert('Please select an image first.');
                    return;
                }

                // Get cropped canvas (300x300 square)
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                // Convert to base64
                const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);

                // Get crop data
                const cropData = cropper.getData();

                // Create form data
                const formData = new FormData();
                formData.append('profile_picture', croppedImageData);
                formData.append('x', Math.round(cropData.x));
                formData.append('y', Math.round(cropData.y));
                formData.append('width', Math.round(cropData.width));
                formData.append('height', Math.round(cropData.height));
                formData.append('_token', '{{ csrf_token() }}');

                // Show loading state
                const saveButton = event.target;
                const originalText = saveButton.textContent;
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';

                // Submit form
                fetch('{{ route('profile.picture.update') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                    .then(function(response) {
                        if (response.ok) {
                            return response.json().catch(function() {
                                return {
                                    success: true
                                };
                            });
                        }
                        return response.json().then(function(data) {
                            throw new Error(data.message || 'Failed to save profile picture.');
                        });
                    })
                    .then(function(data) {
                        if (data.success !== false) {
                            window.location.reload();
                        } else {
                            throw new Error(data.message || 'Failed to save profile picture.');
                        }
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                        alert(error.message || 'An error occurred while saving the profile picture.');
                        saveButton.disabled = false;
                        saveButton.textContent = originalText;
                    });
            };

            // Close modal on outside click
            if (cropModal) {
                cropModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        window.closeCropModal();
                    }
                });
            }
        })();
    </script>
@endpush
