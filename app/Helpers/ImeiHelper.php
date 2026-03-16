<?php

namespace App\Helpers;

class ImeiHelper
{
    /**
     * Normalize IMEI: digits only (strip spaces, dashes, BOM).
     */
    public static function digitsOnly(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = trim($value);
        return preg_replace('/\D/', '', $value);
    }

    /**
     * Check if a value is a valid 15-digit IMEI (digits only + Luhn check digit).
     */
    public static function isValidImei(string $value): bool
    {
        $digits = self::digitsOnly($value);
        if (strlen($digits) !== 15) {
            return false;
        }
        return self::passesLuhn($digits);
    }

    /**
     * Luhn algorithm for IMEI (last digit is check digit; double every second digit from the right).
     */
    public static function passesLuhn(string $fifteenDigits): bool
    {
        if (strlen($fifteenDigits) !== 15 || !ctype_digit($fifteenDigits)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $d = (int) $fifteenDigits[$i];
            if (($i % 2) === 1) {
                $d *= 2;
                if ($d >= 10) {
                    $d = (int) ($d / 10) + $d % 10;
                }
            }
            $sum += $d;
        }
        return ($sum % 10) === 0;
    }

    /**
     * Validate a list of IMEI values. Returns ['valid' => string[], 'invalid' => ['value' => 'reason']].
     */
    public static function validateImeis(array $rawImeis): array
    {
        $valid = [];
        $invalid = [];
        $seen = [];

        foreach ($rawImeis as $raw) {
            $digits = self::digitsOnly((string) $raw);
            if ($digits === '' || strtolower($digits) === 'imei') {
                continue;
            }
            if (strlen($digits) !== 15) {
                $invalid[$raw] = 'Must be exactly 15 digits (got ' . strlen($digits) . ').';
                continue;
            }
            if (!self::passesLuhn($digits)) {
                $invalid[$raw] = 'Invalid IMEI check digit.';
                continue;
            }
            if (!isset($seen[$digits])) {
                $seen[$digits] = true;
                $valid[] = $digits;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }
}
