<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;
use Carbon\Carbon;

class Otp extends Model
{
    use HasUuid;

    protected $fillable = [
        'email',
        'phone',
        'otp',
        'type',
        'used',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Check if OTP is valid and not expired
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Mark OTP as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Generate a 6-digit OTP
     */
    public static function generate(): string
    {
        return str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP for email (used when email is the only identifier).
     */
    public static function createForEmail(string $email, string $type = 'login', int $expiryMinutes = 10): self
    {
        self::where('email', $email)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->update(['used' => true]);

        return self::create([
            'email' => $email,
            'phone' => null,
            'otp' => self::generate(),
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Create a new OTP for a user (stores both email and phone for verification by either).
     */
    public static function createForUser(\App\Models\User $user, string $type = 'login', int $expiryMinutes = 10): self
    {
        $q = self::where('type', $type)->where('used', false)->where('expires_at', '>', now());
        $q->where(function ($q) use ($user) {
            if ($user->email) {
                $q->orWhere('email', $user->email);
            }
            if ($user->phone) {
                $std = \App\Helpers\SmsHelper::standardize($user->phone);
                $q->orWhere('phone', $user->phone)->orWhere('phone', $std);
            }
        });
        $q->update(['used' => true]);

        $phone = $user->phone ? \App\Helpers\SmsHelper::standardize($user->phone) : null;
        $attributes = [
            'phone' => $phone,
            'otp' => self::generate(),
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ];
        // Only set email when user has one (column must be nullable for phone-only users)
        if (!empty($user->email)) {
            $attributes['email'] = $user->email;
        }

        return self::create($attributes);
    }

    /**
     * Find a valid OTP by login value (email or phone as entered by user).
     */
    public static function findForLoginVerification(string $loginValue, string $type = 'login'): ?self
    {
        $normalizedPhone = \App\Helpers\SmsHelper::standardize($loginValue);

        return self::where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->where(function ($q) use ($loginValue, $normalizedPhone) {
                $q->where('email', $loginValue)
                    ->orWhere('phone', $loginValue)
                    ->orWhere('phone', $normalizedPhone);
            })
            ->latest()
            ->first();
    }
}
