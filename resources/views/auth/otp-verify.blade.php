@extends('layouts.auth')

@section('title', 'Verify OTP - Stock Management')

@section('content')
    <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-2">Two-step verification</p>
    <h1 class="text-2xl font-semibold text-primary tracking-tight text-center mb-2">Enter verification code</h1>
    <p class="text-themeBody text-center text-sm mb-8">We sent a 6-digit code to <span
            class="font-medium text-themeBody">{{ $email }}</span></p>

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

    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label for="otp" class="block text-sm font-medium text-themeBody mb-2">6-digit code</label>
            <input type="text" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" required
                autocomplete="one-time-code"
                class="w-full px-4 py-3 border border-themeBorder rounded-lg text-themeHeading text-center text-2xl font-semibold tracking-[0.4em] placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                placeholder="000000" inputmode="numeric">
            @error('otp')
                <p class="mt-1.5 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="w-full py-3 px-4 rounded-lg font-medium text-white bg-primary hover:bg-primary-dark shadow-soft hover:shadow-md transition-all duration-200 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Verify code</span>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-themeBorder space-y-3 text-center">
        <form method="POST" action="{{ route('otp.request') }}" class="inline" id="resendForm">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-sm font-medium text-primary hover:text-primary-dark transition">
                Resend code
            </button>
        </form>
        <div>
            <a href="{{ route('otp.login') }}"
                class="text-sm font-medium text-themeBody hover:text-themeHeading transition inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Use different email
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        document.getElementById('otp').focus();

        // Disable main form on submit
        document.addEventListener('DOMContentLoaded', function() {
            let isSubmitting = false;
            const form = document.querySelector('form[action="{{ route('otp.verify') }}"]');
            if (form) {
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

            // Disable resend form on submit
            let isResending = false;
            const resendForm = document.getElementById('resendForm');
            if (resendForm) {
                resendForm.addEventListener('submit', function(e) {
                    if (isResending) {
                        e.preventDefault();
                        return false;
                    }
                    isResending = true;

                    // Only disable the submit button
                    const resendButton = resendForm.querySelector('button[type="submit"]');
                    if (resendButton) {
                        resendButton.disabled = true;
                    }
                });
            }
        });
    </script>
@endpush
