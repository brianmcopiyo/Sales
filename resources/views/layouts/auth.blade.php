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
        $primaryHex = $theme['primary'] ?? '#006F78';
        $primaryDarkHex = $theme['primary_dark'] ?? '#005a62';
        $themeHeading = $theme['text_heading'] ?? '#111827';
        $themeBody = $theme['text_body'] ?? '#1f2937';
        $themeMuted = $theme['text_muted'] ?? '#6b7280';
        $themePage = $theme['bg_page'] ?? '#f8fafc';
        $themeCard = $theme['bg_card'] ?? '#ffffff';
        $themeInput = $theme['bg_input'] ?? '#f9fafb';
        $themeHover = $theme['bg_hover'] ?? '#f3f4f6';
        $themeBorder = $theme['border_color'] ?? '#e5e7eb';
    @endphp
    <script src="https://cdn.tailwindcss.com"></script>
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
                        themeInput: '{{ $themeInput }}',
                        themeHover: '{{ $themeHover }}',
                        themeBorder: '{{ $themeBorder }}'
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 111, 120, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-weight: 400;
        }
    </style>
</head>

<body class="bg-themePage text-themeBody antialiased font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-themeCard rounded-2xl border border-themeBorder shadow-soft p-8 sm:p-10">
            <a href="{{ url('/') }}" class="flex justify-center mb-8">
                <img src="{{ $logoUrl }}" alt="Stock Management" class="h-10 w-auto object-contain">
            </a>
            @yield('content')
        </div>
        @hasSection('footer')
            <div class="mt-6 text-center">
                @yield('footer')
            </div>
        @endif
    </div>
    @stack('scripts')
</body>

</html>
