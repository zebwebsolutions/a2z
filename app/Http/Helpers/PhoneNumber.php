<?php

namespace App\Http\Helpers;

class PhoneNumber
{
    /**
     * Normalize a Kuwait phone number to E.164 (+965XXXXXXXX).
     * Returns null when normalization fails.
     */
    public static function normalizeKuwait(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        // Convert Arabic/Persian numerals to ASCII digits.
        $value = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        // Support 00965XXXXXXXX format.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Support local leading zero (e.g. 097764165).
        if (strlen($digits) === 9 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Local Kuwait number -> prepend country code.
        if (strlen($digits) === 8) {
            $digits = '965' . $digits;
        }

        if (!str_starts_with($digits, '965') || strlen($digits) !== 11) {
            return null;
        }

        return '+' . $digits;
    }
}

