@extends('layouts.auth')

@section('title', 'Reset Password - Stock Management')

@section('content')
    <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-2">Set new password</p>
    <h1 class="text-2xl font-semibold text-primary tracking-tight text-center mb-8">Reset your password</h1>

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

    <form method="POST" action="{{ route('password.reset') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label for="password" class="block text-sm font-medium text-themeBody mb-2">New password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                class="w-full px-4 py-3 border border-themeBorder rounded-lg text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('password') border-red-300 @enderror"
                placeholder="••••••••">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-themeBody mb-2">Confirm
                password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                autocomplete="new-password"
                class="w-full px-4 py-3 border border-themeBorder rounded-lg text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                placeholder="••••••••">
        </div>

        <button type="submit"
            class="w-full py-3 px-4 rounded-lg font-medium text-white bg-primary hover:bg-primary-dark shadow-soft hover:shadow-md transition-all duration-200 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                </path>
            </svg>
            <span>Reset password</span>
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
                    }
                });
            }
        });
    </script>
@endpush
