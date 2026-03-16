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

<body class="text-themeBody antialiased font-sans min-h-screen {{ $useCustomDashboardBg ? '' : 'bg-themePage' }}"
    @if ($useCustomDashboardBg) style="{{ $dashboardBgStyle }}" @endif>
    <div class="min-h-screen">
        @if (session('success'))
            <div class="mx-auto max-w-2xl px-4 pt-6 sm:px-6">
                <div class="rounded-xl bg-primary px-4 py-3 text-sm font-medium text-white shadow-card">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('warning'))
            <div class="mx-auto max-w-2xl px-4 pt-6 sm:px-6">
                <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    {{ session('warning') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto max-w-2xl px-4 pt-6 sm:px-6">
                <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </div>
    @stack('scripts')
</body>

</html>
