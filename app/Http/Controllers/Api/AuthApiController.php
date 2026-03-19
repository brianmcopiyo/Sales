<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Helpers\SmsHelper;

class AuthApiController extends Controller
{
    /**
     * Mobile login: email/phone + password.
     * When require_otp_for_api is true: returns requires_otp + pending_token; client must call verify-otp.
     * Otherwise returns token + user.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $normalizedPhone = SmsHelper::standardize($login);

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
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->trashed()) {
            return response()->json(['message' => 'Account deleted.'], 403);
        }

        if ($user->isSuspended()) {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        if (config('app.require_otp_for_api', true)) {
            $user->tokens()->where('name', 'otp-pending')->delete();
            $otp = Otp::createForUser($user, 'login', 10);
            $expiryMinutes = 10;
            $email = trim((string) ($user->email ?? ''));
            $phone = trim((string) ($user->phone ?? ''));
            if ($email !== '') {
                Mail::to($user->email)->send(new OtpMail($otp->otp, $expiryMinutes));
            }
            if ($phone !== '') {
                SmsHelper::send($user->phone, "Your login OTP is: {$otp->otp}. Valid for {$expiryMinutes} minutes.");
            }
            $channels = array_filter([$email !== '' ? 'email' : null, $phone !== '' ? 'phone' : null]);
            $channelText = count($channels) > 0 ? implode(' and ', $channels) : 'registered channel';
            $token = $user->createToken('otp-pending', ['otp-pending'])->plainTextToken;
            return response()->json([
                'requires_otp' => true,
                'pending_token' => $token,
                'message' => 'OTP sent to your ' . $channelText . '. Verify to complete login.',
            ]);
        }

        $user->tokens()->where('name', 'distribution-mobile')->delete();
        $token = $user->createToken('distribution-mobile', ['full'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userResponse($user),
        ]);
    }

    /**
     * Verify OTP and exchange pending_token for full token. Requires Bearer pending_token.
     */
    public function verifyOtp(Request $request)
    {
        if (!$request->user()->currentAccessToken()?->can('otp-pending')) {
            return response()->json(['message' => 'Invalid or expired session. Please login again.'], 403);
        }

        $request->validate(['otp' => 'required|string|size:6']);

        $user = $request->user();
        $loginValue = $user->email ?: $user->phone;
        if (empty($loginValue)) {
            return response()->json(['message' => 'Account has no email or phone for OTP.'], 400);
        }

        $otpRecord = Otp::findForLoginVerification($loginValue, 'login');
        if (!$otpRecord || $otpRecord->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $otpRecord->markAsUsed();
        $request->user()->currentAccessToken()->delete();
        $token = $user->createToken('distribution-mobile', ['full'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userResponse($user),
        ]);
    }

    /**
     * Resend OTP. Requires Bearer pending_token.
     */
    public function resendOtp(Request $request)
    {
        if (!$request->user()->currentAccessToken()?->can('otp-pending')) {
            return response()->json(['message' => 'Invalid or expired session. Please login again.'], 403);
        }

        $user = $request->user();
        $otp = Otp::createForUser($user, 'login', 10);
        $expiryMinutes = 10;
        $email = trim((string) ($user->email ?? ''));
        $phone = trim((string) ($user->phone ?? ''));
        if ($email !== '') {
            Mail::to($user->email)->send(new OtpMail($otp->otp, $expiryMinutes));
        }
        if ($phone !== '') {
            SmsHelper::send($user->phone, "Your login OTP is: {$otp->otp}. Valid for {$expiryMinutes} minutes.");
        }

        return response()->json(['message' => 'OTP sent.']);
    }

    /**
     * GET /api/user — current user with branch_id, phone, branch (for app profile/dashboard).
     */
    public function user(Request $request)
    {
        return response()->json($this->userResponse($request->user()));
    }

    /**
     * User payload for login/verify responses (id, name, email, phone, branch_id, branch).
     */
    private function userResponse(User $user): array
    {
        $user->load('branch:id,name');
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'branch_id' => $user->branch_id,
            'branch' => $user->branch ? ['id' => $user->branch->id, 'name' => $user->branch->name] : null,
        ];
    }
}
