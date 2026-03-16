<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stock Management')</title>
    @php
        $logoUrl = file_exists(public_path('logo.jpg')) ? asset('logo.jpg') : asset('assets/img/logo.jpeg');
    @endphp
    <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    @php
        $themeKey = auth()->check() ? auth()->user()->getThemeKey() : 'default';
        $themeConfig = config('themes', []);
        $theme =
            $themeConfig[$themeKey] ??
            ($themeConfig['default'] ?? ['primary' => '#006F78', 'primary_dark' => '#005a62']);
        $primaryHex = $theme['primary'] ?? '#006F78';
        $primaryDarkHex = $theme['primary_dark'] ?? '#005a62';
        $themeHeading = $theme['text_heading'] ?? '#111827';
        $themeBody = $theme['text_body'] ?? '#1f2937';
        $themeMuted = $theme['text_muted'] ?? '#6b7280';
        $themePage = $theme['bg_page'] ?? '#f8fafc';
        $themeCard = $theme['bg_card'] ?? '#ffffff';
        $themeNav = $theme['bg_nav'] ?? '#ffffff';
        $themeInput = $theme['bg_input'] ?? '#f9fafb';
        $themeHover = $theme['bg_hover'] ?? '#f3f4f6';
        $themeBorder = $theme['border_color'] ?? '#e5e7eb';
    @endphp
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif']
                    },
                    colors: {
                        primary: {
                            DEFAULT: '{{ $primaryHex }}',
                            dark: '{{ $primaryDarkHex }}'
                        },
                        themeHeading: '{{ $themeHeading }}',
                        themeBody: '{{ $themeBody }}',
                        themeMuted: '{{ $themeMuted }}',
                        themePage: '{{ $themePage }}',
                        themeCard: '{{ $themeCard }}',
                        themeNav: '{{ $themeNav }}',
                        themeInput: '{{ $themeInput }}',
                        themeHover: '{{ $themeHover }}',
                        themeBorder: '{{ $themeBorder }}'
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 111, 120, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-weight: 400;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Theme-aware form controls (dark mode compatible) */
        .theme-form-context input:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=hidden]),
        .theme-form-context select,
        .theme-form-context textarea {
            background-color: {{ $themeInput }};
            border-color: {{ $themeBorder }};
            color: {{ $themeBody }};
        }

        .theme-form-context input::placeholder,
        .theme-form-context textarea::placeholder {
            color: {{ $themeMuted }};
        }
    </style>
</head>

@php
    $dashboardBgStyle = auth()->check() ? auth()->user()->getDashboardBackgroundCss() : null;
    $themeKeyForBg = auth()->check() ? auth()->user()->getThemeKey() : 'default';
    $useCustomDashboardBg = $dashboardBgStyle && $themeKeyForBg !== 'dark';
@endphp

<body class="text-themeBody antialiased font-sans {{ $useCustomDashboardBg ? '' : 'bg-themePage' }}"
    @if ($useCustomDashboardBg) style="{{ $dashboardBgStyle }}" @endif>
    @auth
        <!-- Top Navbar: translucent with backdrop; slightly more opaque when custom background for contrast -->
        <nav
            class="fixed top-0 left-0 right-0 backdrop-blur-md border-b border-themeBorder z-30 shadow-soft {{ $dashboardBgStyle ? 'bg-themeNav/90' : 'bg-themeNav/98' }}">
            <div class="flex h-[4.5rem] min-h-[72px]">
                <div class="w-64 hidden lg:flex items-center px-5 border-r border-themeBorder">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                        <img src="{{ $logoUrl }}" alt="Stock Management" class="h-8 w-auto object-contain">
                    </a>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="max-w-7xl mx-auto h-full flex justify-between items-center px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center min-w-0 flex-1">
                            <div class="flex items-center gap-3 lg:hidden">
                                <button id="sidebar-toggle"
                                    class="p-2.5 rounded-lg text-themeBody hover:text-primary hover:bg-themeHover transition"
                                    aria-label="Menu">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                <a href="{{ route('dashboard') }}" class="flex items-center">
                                    <img src="{{ $logoUrl }}" alt="Stock Management"
                                        class="h-7 w-auto object-contain">
                                </a>
                            </div>
                            <div class="hidden lg:block relative flex-1 max-w-md ml-4">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-themeMuted" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" id="global-search" placeholder="Search..."
                                    class="block w-full pl-10 pr-4 py-2.5 border border-themeBorder rounded-lg bg-themeInput/80 placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm font-medium text-themeBody cursor-pointer transition">
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- Notifications bell --}}
                            @php $navUnreadCount = $notificationUnreadCount ?? 0; @endphp
                            <div class="relative" x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                    class="relative inline-flex p-2.5 rounded-lg bg-themeInput hover:bg-themeHover text-themeBody hover:text-primary transition focus:outline-none focus:ring-2 focus:ring-primary/20 overflow-visible"
                                    aria-label="Notifications {{ $navUnreadCount > 0 ? '(' . $navUnreadCount . ' unread)' : '' }}">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    @if ($navUnreadCount > 0)
                                        <span
                                            class="absolute top-0 right-0 flex h-[1.125rem] min-w-[1.125rem] max-w-[1.5rem] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-white shadow-md ring-2 ring-white z-10">{{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}</span>
                                    @endif
                                </button>
                                <div x-show="open" @click.away="open = false" x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-80 max-h-[24rem] overflow-hidden bg-themeCard rounded-xl border border-themeBorder shadow-soft z-50 flex flex-col">
                                    <div class="p-3 border-b border-themeBorder flex items-center justify-between">
                                        <span class="text-sm font-semibold text-themeHeading">Notifications</span>
                                        @if ($navUnreadCount > 0)
                                            <form method="POST" action="{{ route('notifications.mark-all-read') }}"
                                                class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="text-xs font-medium text-primary hover:underline">Mark all
                                                    read</button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="overflow-y-auto flex-1">
                                        @if (isset($notificationRecent) && $notificationRecent->isNotEmpty())
                                            @foreach ($notificationRecent as $notification)
                                                @php
                                                    $data = $notification->data ?? [];
                                                    $url =
                                                        $data['action_url'] ??
                                                        route('notifications.mark-read', $notification->id);
                                                @endphp
                                                <a href="{{ route('notifications.mark-read', $notification->id) }}"
                                                    class="block px-3 py-2.5 border-b border-themeBorder hover:bg-themeInput/80 transition {{ $notification->read_at ? 'opacity-80' : 'bg-primary/5' }}">
                                                    <div class="text-sm font-medium text-themeHeading truncate">
                                                        {{ $data['title'] ?? 'Notification' }}</div>
                                                    <div class="text-xs text-themeBody truncate mt-0.5">
                                                        {{ $data['message'] ?? '' }}</div>
                                                    <div class="text-xs text-themeMuted mt-1">
                                                        {{ $notification->created_at->diffForHumans() }}</div>
                                                </a>
                                            @endforeach
                                        @else
                                            <div class="px-3 py-6 text-center text-sm text-themeMuted">No notifications yet.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-2 border-t border-themeBorder">
                                        <a href="{{ route('notifications.index') }}"
                                            class="block text-center text-sm font-medium text-primary hover:underline py-1.5">View
                                            all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open"
                                    class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg bg-themeInput hover:bg-themeHover text-themeBody hover:text-primary font-medium text-sm transition focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    <x-profile-picture :user="auth()->user()" size="sm" />
                                    <span class="max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                                    <svg class="w-4 h-4 text-themeMuted" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-56 bg-themeCard rounded-xl border border-themeBorder shadow-soft z-50"
                                    style="display: none;">
                                    <div class="p-3 border-b border-themeBorder">
                                        <div class="flex items-center gap-3 mb-2">
                                            <x-profile-picture :user="auth()->user()" size="md" />
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-semibold text-themeHeading truncate">
                                                    {{ auth()->user()->name }}
                                                </div>
                                                <div class="text-xs font-medium text-themeMuted truncate">
                                                    {{ auth()->user()->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="py-1.5">
                                        <a href="{{ route('profile.show') }}"
                                            class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Profile
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit"
                                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-red-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                </svg>
                                                Logout
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside id="sidebar"
            class="fixed top-[4.5rem] left-0 h-[calc(100vh-4.5rem)] w-64 bg-themeNav border-r border-themeBorder transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-20 overflow-y-auto shadow-soft">
            <nav class="p-4 space-y-0.5">
                @include('partials.navigation')
            </nav>
        </aside>

        <div id="sidebar-overlay"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-10 lg:hidden hidden transition-opacity"></div>

        <!-- Main Content -->
        <main class="pt-[4.5rem] lg:pl-64 min-h-screen theme-form-context">
            <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <div class="mb-6 px-4 py-3 rounded-xl bg-primary text-white text-sm font-medium shadow-card">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div
                        class="mb-6 px-4 py-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-sm font-medium">
                        {{ session('warning') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('import_errors') && count(session('import_errors')) > 0)
                    <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-100">
                        <p class="text-sm font-semibold text-red-800 mb-2">Import errors (row-level)</p>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1 max-h-48 overflow-y-auto">
                            @foreach (session('import_errors') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <script>
            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                    sidebarOverlay.classList.toggle('hidden');
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                });
            }
        </script>

        <!-- Search Popup Modal -->
        <div id="search-modal"
            class="hidden fixed inset-0 overflow-y-auto h-full w-full z-50 bg-black/20 backdrop-blur-sm">
            <div
                class="relative top-16 mx-auto p-6 w-full max-w-2xl bg-themeCard rounded-2xl border border-themeBorder shadow-soft my-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-xl font-semibold text-primary tracking-tight" id="modal-title">Search</h3>
                    <button type="button" id="search-modal-close"
                        class="p-2 rounded-lg text-themeMuted hover:text-primary hover:bg-themeHover transition focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-themeMuted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" id="search-modal-input" placeholder="Search..." autocomplete="off"
                        class="block w-full pl-11 pr-4 py-3 border border-themeBorder rounded-xl bg-themeInput placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary text-base font-medium text-themeHeading transition">
                </div>
                <div id="search-suggestions" class="mt-4 max-h-96 overflow-y-auto rounded-xl">
                    <div class="text-center py-10 text-themeMuted font-medium text-sm">
                        Start typing to search...
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Search Modal Functionality
            (function() {
                const searchInput = document.getElementById('global-search');
                const searchModal = document.getElementById('search-modal');
                const searchModalInput = document.getElementById('search-modal-input');
                const searchModalClose = document.getElementById('search-modal-close');
                const searchSuggestions = document.getElementById('search-suggestions');
                let searchTimeout = null;

                // Open modal when search input is clicked
                if (searchInput) {
                    searchInput.addEventListener('click', function() {
                        searchModal.classList.remove('hidden');
                        setTimeout(() => {
                            searchModalInput.focus();
                        }, 100);
                        performSearch('');
                    });
                }

                // Close modal
                function closeModal() {
                    searchModal.classList.add('hidden');
                    searchModalInput.value = '';
                }

                if (searchModalClose) {
                    searchModalClose.addEventListener('click', closeModal);
                }

                // Close on backdrop click
                if (searchModal) {
                    searchModal.addEventListener('click', function(e) {
                        if (e.target === searchModal) {
                            closeModal();
                        }
                    });
                }

                // Close on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !searchModal.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                // Perform search with debounce
                if (searchModalInput) {
                    searchModalInput.addEventListener('input', function(e) {
                        const query = e.target.value.trim();

                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            performSearch(query);
                        }, 300);
                    });

                    // Handle Enter key
                    searchModalInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            const query = e.target.value.trim();
                            if (query) {
                                // Navigate to first result or perform full search
                                const firstResult = searchSuggestions.querySelector('a[href]');
                                if (firstResult) {
                                    window.location.href = firstResult.href;
                                }
                            }
                        }
                    });
                }

                function performSearch(query) {
                    const url = new URL('{{ route('search') }}', window.location.origin);
                    url.searchParams.append('q', query);

                    // Show loading state
                    searchSuggestions.innerHTML =
                        '<div class="text-center py-10 text-themeMuted font-medium text-sm"><p>Searching...</p></div>';

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            displaySuggestions(data.suggestions, query);
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                            searchSuggestions.innerHTML =
                                '<div class="text-center py-10 text-red-600 font-medium text-sm"><p>Error performing search. Please try again.</p></div>';
                        });
                }

                function displaySuggestions(suggestions, query) {
                    if (!suggestions || suggestions.length === 0) {
                        searchSuggestions.innerHTML =
                            '<div class="text-center py-10 text-themeMuted font-medium text-sm"><p>No results found</p></div>';
                        return;
                    }

                    let html = '';
                    suggestions.forEach(section => {
                        if (section.items && section.items.length > 0) {
                            html += `<div class="mb-5">`;
                            html +=
                                `<h3 class="text-xs font-semibold text-themeMuted uppercase tracking-wider mb-2 px-2">${escapeHtml(section.section)}</h3>`;
                            html += `<div class="space-y-0.5">`;

                            section.items.forEach(item => {
                                const iconSvg = item.icon ?
                                    `<svg class="w-5 h-5 text-themeMuted group-hover:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${escapeHtml(item.icon)}"></path></svg>` :
                                    '';
                                html += `
                                    <a href="${item.url}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-themeHover hover:text-primary transition group">
                                        <div class="flex-shrink-0">${iconSvg}</div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-themeHeading group-hover:text-primary">${escapeHtml(item.title)}</p>
                                            ${item.subtitle ? `<p class="text-xs text-themeMuted truncate font-medium">${escapeHtml(item.subtitle)}</p>` : ''}
                                        </div>
                                        <svg class="w-4 h-4 text-themeMuted group-hover:text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                `;
                            });
                            html += `</div></div>`;
                        }
                    });

                    searchSuggestions.innerHTML = html ||
                        '<div class="text-center py-10 text-themeMuted font-medium text-sm"><p>No results found</p></div>';
                }

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            })();
        </script>
    @endauth

    @guest
        <main class="pb-8 min-h-screen bg-[#f8fafc]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                @if (session('success'))
                    <div class="mb-6 px-4 py-3 rounded-xl bg-primary text-white text-sm font-medium shadow-card">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div
                        class="mb-6 px-4 py-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-sm font-medium">
                        {{ session('warning') }}
                    </div>
                @endif

                @if (session('import_errors') && count(session('import_errors')) > 0)
                    <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-100">
                        <p class="text-sm font-semibold text-red-800 mb-2">Import errors (row-level)</p>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1 max-h-48 overflow-y-auto">
                            @foreach (session('import_errors') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    @endguest

    {{-- Add to cart via JS (no page reload) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form || !form.classList.contains('js-add-to-cart')) return;
                e.preventDefault();

                const btn = form.querySelector('.js-add-to-cart-btn');
                const originalHtml = btn ? btn.innerHTML : '';
                if (btn) {
                    btn.disabled = true;
                    if (btn.textContent.trim()) btn.textContent = 'Adding…';
                }

                const url = form.action;
                const body = new FormData(form);
                const token = document.querySelector('meta[name="csrf-token"]');
                if (token && !body.has('_token')) body.append('_token', token.getAttribute('content'));

                fetch(url, {
                        method: 'POST',
                        body: body,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        var countEl = document.getElementById('cart-count');
                        if (countEl && typeof data.cart_count !== 'undefined') countEl.textContent =
                            data.cart_count;
                        var cartLink = document.querySelector('a[aria-label^="Cart"]');
                        if (cartLink) cartLink.setAttribute('aria-label', 'Cart (' + (data.cart_count ||
                            0) + ' items)');
                        if (btn) {
                            btn.disabled = false;
                            if (btn.textContent.trim()) {
                                btn.textContent = 'Added';
                                setTimeout(function() {
                                    btn.innerHTML = originalHtml;
                                }, 1500);
                            } else {
                                btn.innerHTML = originalHtml;
                            }
                        }
                    })
                    .catch(function() {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        }
                        form.submit();
                    });
            });
        });
    </script>
    @stack('scripts')
</body>

</html>
