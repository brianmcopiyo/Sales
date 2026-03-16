<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'TajaCore') . ' - Sales & Inventory Management')</title>
    @php
        $logoUrl = file_exists(public_path('logo.jpg')) ? asset('logo.jpg') : asset('assets/img/logo.jpeg');
    @endphp
    <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    @php
        $themeKey = 'default';
        $themeConfig = config('themes', []);
        $theme = $themeConfig[$themeKey] ?? ($themeConfig['default'] ?? []);
        $themeHeading = $theme['text_heading'] ?? '#111827';
        $themeBody = $theme['text_body'] ?? '#1f2937';
        $themeMuted = $theme['text_muted'] ?? '#6b7280';
        $themeCard = $theme['bg_card'] ?? '#ffffff';
        $themeBorder = $theme['border_color'] ?? '#e5e7eb';
        $themePage = $theme['bg_page'] ?? '#f8fafc';
        $themeInput = $theme['bg_input'] ?? '#f9fafb';
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
                            DEFAULT: '#006F78',
                            dark: '#005a62',
                            light: '#0d9488'
                        },
                        surface: {
                            DEFAULT: '#f8fafc',
                            muted: '#f1f5f9'
                        }
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 111, 120, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'soft-lg': '0 4px 25px -5px rgba(0, 111, 120, 0.08), 0 10px 30px -5px rgba(0, 0, 0, 0.05)',
                        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05)',
                        'card-hover': '0 10px 40px -10px rgba(0, 111, 120, 0.12), 0 4px 15px -3px rgba(0, 0, 0, 0.06)'
                    }
                }
            }
        }
    </script>
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            font-weight: 400;
        }

        [x-cloak] {
            display: none !important;
        }

        .text-balance {
            text-wrap: balance;
        }

        .nav-link:focus-visible,
        .nav-menu-btn:focus-visible,
        .nav-btn-login:focus-visible {
            outline: 2px solid #006F78;
            outline-offset: 2px;
        }

        #main-nav.nav-over-dark .nav-link:focus-visible,
        #main-nav.nav-over-dark .nav-menu-btn:focus-visible,
        #main-nav.nav-over-dark .nav-btn-login:focus-visible {
            outline-color: #fff;
        }
    </style>
</head>

<body class="bg-themePage text-themeBody antialiased font-sans">
    @yield('content')
</body>

</html>
