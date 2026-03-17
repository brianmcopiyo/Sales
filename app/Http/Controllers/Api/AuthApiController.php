<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\SmsHelper;

class AuthApiController extends Controller
{
    /**
     * Mobile login: email/phone + password. Returns Sanctum token (no OTP for API).
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

        $user->tokens()->where('name', 'distribution-mobile')->delete();
        $token = $user->createToken('distribution-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
