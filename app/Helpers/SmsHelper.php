<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsHelper
{
    /**
     * Timeout in seconds for the SMS gateway request.
     */
    protected static int $timeout = 10;

    /**
     * SMS gateway base URL (configurable via env SMS_GATEWAY_URL).
     */
    protected static function baseUrl(): string
    {
        return config('services.sms.url', 'http://10.0.13.10:8081/sms/v1/send');
    }

    /**
     * Standardize phone number for the SMS gateway (e.g. E.164 or 255xxxxxxxxx for Tanzania).
     * Strips all non-digits (including +), then normalizes:
     * - +255651033074 or 255651033074 (12 digits) -> returned as-is
     * - 0651033074 (10 digits, leading 0) -> 255651033074
     * - 651033074 (9 digits) -> 255651033074
     */
    public static function standardize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return $phone;
        }
        // Already in 255xxxxxxxxx or +255 form (12 digits) – return as-is
        if (strlen($digits) === 12 && str_starts_with($digits, '255')) {
            return $digits;
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '255' . $digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '255' . substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '255' . $digits;
        }
        return $digits;
    }

    /**
     * Send an SMS via the gateway.
     * On connection timeout or other failure, logs the error and returns null without throwing.
     *
     * @param  string  $phone  Recipient phone number (will be standardized)
     * @param  string  $message  Message text
     * @param  string|null  $from  Sender ID (default: INFO)
     * @return \Illuminate\Http\Client\Response|null
     */
    public static function send(string $phone, string $message, ?string $from = null)
    {
        $from = $from ?? config('services.sms.from', 'INFO');
        $data = [
            'to' => self::standardize($phone),
            'from' => $from,
            'text' => $message,
        ];

        try {
            $response = Http::timeout(self::$timeout)->post(self::baseUrl(), $data);

            Log::info('SMS send response', [
                'to' => $data['to'],
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return $response;
        } catch (ConnectionException $e) {
            Log::warning('SMS gateway connection failed (timeout or unreachable)', [
                'to' => $data['to'],
                'url' => self::baseUrl(),
                'message' => $e->getMessage(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('SMS send failed', [
                'to' => $data['to'],
                'url' => self::baseUrl(),
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
