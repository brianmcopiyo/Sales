@extends('layouts.auth')

@section('title', 'Login - Stock Management')

@section('content')
    <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-2">Sign in</p>
    <h1 class="text-2xl font-semibold text-primary tracking-tight text-center mb-8">Welcome back</h1>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-lg text-sm font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-6 p-4 bg-green-50 border border-green-100 text-green-700 rounded-lg text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="login" class="block text-sm font-medium text-themeBody mb-2">Email or phone</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}" required autocomplete="username"
                class="w-full px-4 py-3 border border-themeBorder rounded-lg text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('login') border-red-300 @enderror"
                placeholder="you@example.com or 0712345678">
            @error('login')
                <p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-themeBody mb-2">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                class="w-full px-4 py-3 border border-themeBorder rounded-lg text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('password') border-red-300 @enderror"
                placeholder="••••••••">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input type="checkbox" id="remember" name="remember"
                class="rounded border-themeBorder text-primary focus:ring-primary/20">
            <label for="remember" class="ml-2 text-sm font-medium text-themeBody">Remember me</label>
        </div>

        <button type="submit"
            class="w-full py-3 px-4 rounded-lg font-medium text-white bg-primary hover:bg-primary-dark shadow-soft hover:shadow-md transition-all duration-200 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            <span>Sign in</span>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-themeBorder text-center">
        <a href="{{ route('password.forgot') }}"
            class="text-sm font-medium text-primary hover:text-primary-dark transition">
            Forgot password?
        </a>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                let isSubmitting = false;
                form.addEventListener('submit', function(e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    isSubmitting = true;

                    // Only disable the submit button to prevent double submission
                    // Don't disable input fields as it prevents their values from being sent
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.style.opacity = '0.6';
                        submitButton.style.cursor = 'not-allowed';
                    }
                });
            }
        });
    </script>
@endpush
