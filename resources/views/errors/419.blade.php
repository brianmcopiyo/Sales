<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Page Expired | Stock Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
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
                            DEFAULT: '#006F78',
                            dark: '#005a62'
                        }
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
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
    </style>
</head>

<body class="bg-themeInput flex items-center justify-center min-h-screen font-medium">
    <div class="max-w-md w-full px-4">
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-8 text-center shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="mb-6 flex justify-center">
                <div class="w-24 h-24 bg-amber-50 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-6xl text-primary font-semibold mb-4">419</h1>
            <h2 class="text-2xl text-themeHeading font-semibold mb-3">Page Expired</h2>
            <p class="text-themeBody font-medium mb-8">
                Your session has expired for security reasons. Please refresh the page and try again.
            </p>
            <div class="space-y-3">
                <button onclick="window.location.reload()"
                    class="block w-full bg-primary text-white py-3 rounded-xl hover:bg-primary-dark transition font-medium shadow-sm flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    <span>Refresh Page</span>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="block w-full bg-themeHover text-themeBody py-3 rounded-xl hover:bg-themeBorder transition font-medium flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        <span>Go to Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="block w-full bg-themeHover text-themeBody py-3 rounded-xl hover:bg-themeBorder transition font-medium flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                            </path>
                        </svg>
                        <span>Go to Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</body>

</html>
