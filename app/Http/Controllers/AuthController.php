<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use App\Models\Otp;
use App\Mail\PasswordResetMail;
use App\Mail\OtpMail;
use App\Helpers\SmsHelper;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
        ]);

        $login = trim($request->input('login'));
        $normalizedPhone = SmsHelper::standardize($login);

        // Find user by email, or by phone (exact or normalized so 0712345678 and 255712345678 both match)
        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->orWhere('phone', $normalizedPhone)
            ->first();

        if (!$user && $normalizedPhone !== '' && $normalizedPhone !== $login) {
            $user = User::whereNotNull('phone')
                ->get()
                ->first(fn ($u) => $u->phone && SmsHelper::standardize($u->phone) === $normalizedPhone);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('login'));
        }

        if ($user->trashed()) {
            return back()->withErrors([
                'login' => 'This account has been deleted. Please contact an administrator.',
            ])->withInput($request->only('login'));
        }

        if ($user->isSuspended()) {
            return back()->withErrors([
                'login' => 'This account has been suspended. Please contact an administrator.',
            ])->withInput($request->only('login'));
        }

        if (!$user->email && !$user->phone) {
            return back()->withErrors(['login' => 'This account has no email or phone for OTP. Contact an administrator.']);
        }

        // Generate and store OTP (supports both email and phone)
        $otp = Otp::createForUser($user, 'login', 10);
        $expiryMinutes = 10;

        // Send OTP to both email and SMS when available (skip when credential is null/empty)
        $email = $user->email !== null && $user->email !== '' ? trim((string) $user->email) : '';
        $phone = $user->phone !== null && $user->phone !== '' ? trim((string) $user->phone) : '';

        if ($email !== '') {
            Mail::to($user->email)->send(new OtpMail($otp->otp, $expiryMinutes));
        }
        if ($phone !== '') {
            SmsHelper::send($user->phone, "Your login OTP is: {$otp->otp}. Valid for {$expiryMinutes} minutes.");
        }

        $request->session()->put('pending_login', [
            'user_id' => $user->id,
            'login_value' => $login,
            'remember' => $request->boolean('remember'),
        ]);

        $channels = array_filter([$email !== '' ? 'email' : null, $phone !== '' ? 'phone' : null]);
        return redirect()->route('login.otp')->with('status', 'We have sent an OTP to your ' . implode(' and ', $channels) . '. Please verify to complete login.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password request (accept email or phone).
     * Sends reset link by email if user has email, otherwise by SMS to phone.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|max:255',
        ]);

        $identifier = trim($request->input('email'));
        $normalizedPhone = SmsHelper::standardize($identifier);

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('phone', $normalizedPhone)
            ->first();

        if (!$user && $normalizedPhone !== '' && $normalizedPhone !== $identifier) {
            $user = User::whereNotNull('phone')
                ->get()
                ->first(fn ($u) => $u->phone && SmsHelper::standardize($u->phone) === $normalizedPhone);
        }

        if (!$user) {
            return back()->withErrors(['email' => 'We could not find a user with that email or phone number.']);
        }

        if (!$user->email && !$user->phone) {
            return back()->withErrors(['email' => 'This account has no email or phone for password reset.']);
        }

        // Token key: email if present, otherwise normalized phone (for lookup on reset)
        $tokenKey = $user->email ?? ($user->phone ? SmsHelper::standardize($user->phone) : null);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $tokenKey],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = url('/reset-password?token=' . $token . '&email=' . urlencode($tokenKey));

        $sentEmail = false;
        $sentSms = false;
        if (!empty($user->email)) {
            Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->email));
            $sentEmail = true;
        }
        if (!empty($user->phone)) {
            SmsHelper::send($user->phone, "Your password reset link: {$resetUrl} (valid for 6 hours).");
            $sentSms = true;
        }

        if (!$sentEmail && !$sentSms) {
            return back()->withErrors(['email' => 'This account has no email or phone for password reset.']);
        }
        $channels = array_filter([$sentEmail ? 'email' : null, $sentSms ? 'mobile' : null]);
        return back()->with('status', 'We have sent your password reset link to your ' . implode(' and ', $channels) . '.');
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(Request $request)
    {
        return view('auth.reset-password', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle password reset (identifier in request is email or normalized phone from forgot step).
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|string|max:255',
            'password' => 'required|min:8|confirmed',
        ]);

        $identifier = $request->input('email');
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $identifier)
            ->first();

        if (!$resetRecord) {
            return back()->withErrors(['token' => 'This reset link has already been used or is invalid. Please request a new one.']);
        }

        if (!Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors(['token' => 'Invalid reset link. Please use the link from your email or SMS.']);
        }

        $createdAt = Carbon::parse($resetRecord->created_at);
        $expiryMinutes = 360; // 6 hours
        if ($createdAt->diffInMinutes(now(), false) > $expiryMinutes) {
            DB::table('password_reset_tokens')->where('email', $identifier)->delete();
            return back()->withErrors(['token' => 'This reset link has expired. Please request a new one.']);
        }

        $normalizedPhone = SmsHelper::standardize($identifier);
        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('phone', $normalizedPhone)
            ->first();

        if (!$user && $normalizedPhone !== '' && $normalizedPhone !== $identifier) {
            $user = User::whereNotNull('phone')
                ->get()
                ->first(fn ($u) => $u->phone && SmsHelper::standardize($u->phone) === $normalizedPhone);
        }

        if (!$user) {
            return back()->withErrors(['email' => 'We could not find the user for this reset link.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $identifier)->delete();

        return redirect('/login')->with('status', 'Your password has been reset successfully!');
    }

    /**
     * Show OTP verification form (after password login)
     */
    public function showLoginOtp()
    {
        $pendingLogin = session('pending_login');

        if (!$pendingLogin) {
            return redirect()->route('login')->withErrors(['login' => 'Please login with your password first.']);
        }

        return view('auth.login-otp', [
            'login_display' => $pendingLogin['login_value'] ?? $pendingLogin['email'] ?? '',
        ]);
    }

    /**
     * Verify OTP and complete login
     */
    public function verifyLoginOtp(Request $request)
    {
        $pendingLogin = session('pending_login');

        if (!$pendingLogin) {
            return redirect()->route('login')->withErrors(['login' => 'Please login with your password first.']);
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $loginValue = $pendingLogin['login_value'] ?? $pendingLogin['email'] ?? '';
        $otp = Otp::findForLoginVerification($loginValue, 'login');

        if (!$otp || $otp->otp !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try logging in again.'])->withInput();
        }

        // Mark OTP as used
        $otp->markAsUsed();

        // Login the user (exclude soft-deleted)
        try {
            $user = User::findOrFail($pendingLogin['user_id']);
        } catch (ModelNotFoundException $e) {
            $request->session()->forget('pending_login');
            return redirect()->route('login')->withErrors(['login' => 'This account has been deleted. Please contact an administrator.']);
        }
        if ($user->trashed()) {
            $request->session()->forget('pending_login');
            return redirect()->route('login')->withErrors(['login' => 'This account has been deleted. Please contact an administrator.']);
        }
        if ($user->isSuspended()) {
            $request->session()->forget('pending_login');
            return redirect()->route('login')->withErrors(['login' => 'This account has been suspended. Please contact an administrator.']);
        }
        Auth::login($user, $pendingLogin['remember']);
        $request->session()->regenerate();

        // Clear pending login session
        $request->session()->forget('pending_login');

        return redirect()->intended('/dashboard');
    }

    /**
     * Resend OTP for login verification
     */
    public function resendLoginOtp(Request $request)
    {
        $pendingLogin = session('pending_login');

        if (!$pendingLogin) {
            return redirect()->route('login')->withErrors(['login' => 'Please login with your password first.']);
        }

        try {
            $user = User::findOrFail($pendingLogin['user_id']);
        } catch (ModelNotFoundException $e) {
            $request->session()->forget('pending_login');
            return redirect()->route('login')->withErrors(['login' => 'This account has been deleted. Please contact an administrator.']);
        }
        if ($user->trashed()) {
            $request->session()->forget('pending_login');
            return redirect()->route('login')->withErrors(['login' => 'This account has been deleted. Please contact an administrator.']);
        }
        $otp = Otp::createForUser($user, 'login', 10);
        $expiryMinutes = 10;

        // Send OTP to both email and SMS when available (skip when credential is null/empty)
        $email = $user->email !== null && $user->email !== '' ? trim((string) $user->email) : '';
        $phone = $user->phone !== null && $user->phone !== '' ? trim((string) $user->phone) : '';

        if ($email !== '') {
            Mail::to($user->email)->send(new OtpMail($otp->otp, $expiryMinutes));
        }
        if ($phone !== '') {
            SmsHelper::send($user->phone, "Your login OTP is: {$otp->otp}. Valid for {$expiryMinutes} minutes.");
        }

        $channels = array_filter([$email !== '' ? 'email' : null, $phone !== '' ? 'phone' : null]);
        return back()->with('status', 'A new OTP has been sent to your ' . implode(' and ', $channels) . '.');
    }
}
