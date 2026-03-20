<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Distributor Portal') — Taja App</title>
    @php
        $logoUrl = file_exists(public_path('logo.jpg')) ? asset('logo.jpg') : asset('assets/img/logo.jpeg');
    @endphp
    <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    @php
        $themeKey = auth()->check() ? auth()->user()->getThemeKey() : 'default';
        $themeConfig = config('themes', []);
        $theme = $themeConfig[$themeKey] ?? ($themeConfig['default'] ?? ['primary' => '#006F78', 'primary_dark' => '#005a62']);
        $primaryHex     = $theme['primary']       ?? '#006F78';
        $primaryDarkHex = $theme['primary_dark']  ?? '#005a62';
        $themePage      = $theme['bg_page']       ?? '#f8fafc';
        $themeCard      = $theme['bg_card']       ?? '#ffffff';
        $themeBorder    = $theme['border_color']  ?? '#e5e7eb';
        $themeBody      = $theme['text_body']     ?? '#1f2937';
        $themeMuted     = $theme['text_muted']    ?? '#6b7280';
    @endphp
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
                    colors: {
                        primary: { DEFAULT: '{{ $primaryHex }}', dark: '{{ $primaryDarkHex }}' },
                        themeBody: '{{ $themeBody }}',
                        themeMuted: '{{ $themeMuted }}',
                        themePage: '{{ $themePage }}',
                        themeCard: '{{ $themeCard }}',
                        themeBorder: '{{ $themeBorder }}'
                    }
                }
            }
        }
    </script>
    @stack('styles')
</head>

<body class="font-sans antialiased" style="background-color: {{ $themePage }}; color: {{ $themeBody }};">

    {{-- Top Navigation Bar --}}
    <nav style="background-color: {{ $themeCard }}; border-bottom: 1px solid {{ $themeBorder }};" class="sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo & Brand --}}
                <div class="flex items-center gap-3">
                    <img src="{{ $logoUrl }}" alt="Taja App" class="h-8 w-8 rounded object-cover">
                    <div>
                        <span class="font-semibold text-sm" style="color: {{ $primaryHex }};">Taja App</span>
                        <span class="ml-1 text-xs font-medium px-2 py-0.5 rounded-full text-white" style="background-color: {{ $primaryHex }};">Distributor Portal</span>
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <div class="hidden md:flex items-center gap-1">
                    @php
                        $portalNav = [
                            ['route' => 'portal.dashboard',        'label' => 'Dashboard'],
                            ['route' => 'portal.orders.index',     'label' => 'Orders'],
                            ['route' => 'portal.schemes.index',    'label' => 'Schemes'],
                            ['route' => 'portal.inventory.index',  'label' => 'Inventory'],
                            ['route' => 'portal.reports.index',    'label' => 'Reports'],
                            ['route' => 'portal.claims.index',     'label' => 'Claims'],
                        ];
                    @endphp
                    @foreach ($portalNav as $nav)
                        @php $active = request()->routeIs($nav['route']) || str_starts_with(request()->route()->getName() ?? '', rtrim($nav['route'], 'index')); @endphp
                        <a href="{{ route($nav['route']) }}"
                           class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                  {{ $active ? 'text-white' : 'hover:text-primary' }}"
                           style="{{ $active ? 'background-color:' . $primaryHex . '; color:#fff;' : 'color:' . $themeBody . ';' }}">
                            {{ $nav['label'] }}
                        </a>
                    @endforeach
                </div>

                {{-- User Info & Logout --}}
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium" style="color: {{ $themeBody }};">{{ auth()->user()->name }}</p>
                        <p class="text-xs" style="color: {{ $themeMuted }};">Distributor</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-md border font-medium transition-colors hover:opacity-80"
                                style="border-color: {{ $themeBorder }}; color: {{ $themeMuted }};">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Mobile Tab Navigation --}}
        <div class="md:hidden border-t overflow-x-auto flex gap-1 px-4 pb-2 pt-1" style="border-color: {{ $themeBorder }};">
            @foreach ($portalNav as $nav)
                @php $active = request()->routeIs($nav['route']); @endphp
                <a href="{{ route($nav['route']) }}"
                   class="flex-shrink-0 px-3 py-1.5 rounded text-xs font-medium whitespace-nowrap
                          {{ $active ? 'text-white' : '' }}"
                   style="{{ $active ? 'background-color:' . $primaryHex . '; color:#fff;' : 'color:' . $themeBody . ';' }}">
                    {{ $nav['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Flash Messages --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        @if (session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error') || $errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                @if (session('error'))
                    {{ session('error') }}
                @else
                    @foreach ($errors->all() as $err) <p>{{ $err }}</p> @endforeach
                @endif
            </div>
        @endif
    </div>

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t py-4 text-center text-xs mt-8" style="border-color: {{ $themeBorder }}; color: {{ $themeMuted }};">
        &copy; {{ date('Y') }} Taja App — Distributor Self-Service Portal
    </footer>

    @stack('scripts')
</body>
</html>
