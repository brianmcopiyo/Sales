@extends('layouts.auth')

@section('title', 'Forgot Password - Stock Management')

@section('content')
    <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-2">Reset password</p>
    <h1 class="text-2xl font-semibold text-primary tracking-tight text-center mb-2">Forgot your password?</h1>
    <p class="text-themeBody text-center text-sm mb-8">Enter your email or phone number and we’ll send you a reset link.</p>

    @if (session('status'))
        <div class="mb-6 p-4 bg-green-50 border border-green-100 text-green-700 rounded-lg text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-lg text-sm font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.forgot') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-themeBody mb-2">Email or phone</label>
            <input type="text" id="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                class="w-full px-4 py-3 border border-themeBorder rounded-lg text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                placeholder="you@example.com or 0712345678">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="w-full py-3 px-4 rounded-lg font-medium text-white bg-primary hover:bg-primary-dark shadow-soft hover:shadow-md transition-all duration-200 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                </path>
            </svg>
            <span>Send reset link</span>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-themeBorder text-center">
        <a href="{{ route('login') }}"
            class="text-sm font-medium text-primary hover:text-primary-dark transition inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to sign in
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
                        const span = submitButton.querySelector('span');
                        if (span) {
                            span.textContent = 'Sending...';
                        }
                    }
                });
            }
        });
    </script>
@endpush
