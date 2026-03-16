@extends('layouts.auth')

@section('title', 'Verify OTP - Stock Management')

@section('content')
    <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-2">Two-step verification</p>
    <h1 class="text-2xl font-semibold text-primary tracking-tight text-center mb-2">Enter verification code</h1>
    <p class="text-themeBody text-center text-sm mb-8">We sent a 6-digit code to <span
            class="font-medium text-themeBody">{{ $login_display }}</span></p>

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

    <form method="POST" action="{{ route('login.otp.verify') }}" id="otpForm" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-themeBody mb-4 text-center">Enter the 6-digit code</label>
            <div class="flex justify-center gap-2 sm:gap-3 mb-4">
                @foreach (['otp1', 'otp2', 'otp3', 'otp4', 'otp5', 'otp6'] as $name)
                    <input type="text" id="{{ $name }}" name="{{ $name }}" maxlength="1" pattern="[0-9]"
                        required
                        class="w-11 h-12 sm:w-12 sm:h-14 text-center text-xl font-semibold border-2 border-themeBorder rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('otp') border-red-300 @enderror"
                        autocomplete="off" inputmode="numeric">
                @endforeach
            </div>
            <input type="hidden" id="otp" name="otp" value="">
            @error('otp')
                <p class="mt-1.5 text-sm text-red-600 font-medium text-center">{{ $message }}</p>
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
        <form method="POST" action="{{ route('login.otp.resend') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm font-medium text-primary hover:text-primary-dark transition">
                Resend code
            </button>
        </form>
        <div>
            <a href="{{ route('login') }}"
                class="text-sm font-medium text-themeBody hover:text-themeHeading transition inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to sign in
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const otpInputs = ['otp1', 'otp2', 'otp3', 'otp4', 'otp5', 'otp6'];
            const form = document.getElementById('otpForm');
            const hiddenInput = document.getElementById('otp');

            function updateHiddenOtp() {
                hiddenInput.value = otpInputs.map(id => document.getElementById(id).value).join('');
            }

            otpInputs.forEach((id, index) => {
                const input = document.getElementById(id);
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    updateHiddenOtp();
                    if (this.value && index < otpInputs.length - 1) document.getElementById(otpInputs[
                        index + 1]).focus();
                });
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasted = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                    if (pasted.length > 0) {
                        for (let i = 0; i < pasted.length && (index + i) < otpInputs.length; i++)
                            document.getElementById(otpInputs[index + i]).value = pasted[i];
                        updateHiddenOtp();
                        const next = Math.min(index + pasted.length, otpInputs.length - 1);
                        document.getElementById(otpInputs[next]).focus();
                    }
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        document.getElementById(otpInputs[index - 1]).focus();
                        document.getElementById(otpInputs[index - 1]).value = '';
                        updateHiddenOtp();
                    }
                    if (e.key === 'ArrowLeft' && index > 0) {
                        e.preventDefault();
                        document.getElementById(otpInputs[index - 1]).focus();
                    }
                    if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                        e.preventDefault();
                        document.getElementById(otpInputs[index + 1]).focus();
                    }
                });
            });

            let isSubmitting = false;
            form.addEventListener('submit', function(e) {
                updateHiddenOtp();
                if (hiddenInput.value.length !== 6) {
                    e.preventDefault();
                    return false;
                }
                
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

            // Also disable the resend form on submit
            let isResending = false;
            const resendForm = document.querySelector('form[action="{{ route('login.otp.resend') }}"]');
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

            document.getElementById('otp1').focus();
        })();
    </script>
@endpush
