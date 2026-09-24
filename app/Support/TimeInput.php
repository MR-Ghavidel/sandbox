<?php

namespace App\Support;

class TimeInput
{
    /**
     * Normalize a typed time to "HH:MM": Persian/Arabic digits become Latin, "8:5" becomes "08:05",
     * "830" becomes "08:30". Empty values and "00:00" (Bizagi's empty cell) become null. Anything
     * that cannot be understood is returned trimmed so that validation can reject it.
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim(self::latinDigits((string) $value));

        if ($value === '' || $value === '00:00' || $value === '0:00') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[:.](\d{1,2})$/', $value, $matches) || preg_match('/^(\d{1,2})(\d{2})$/', $value, $matches)) {
            return sprintf('%02d:%02d', $matches[1], $matches[2]);
        }

        return $value;
    }

    /**
     * Replace Persian (۰-۹) and Arabic (٠-٩) digits with Latin digits.
     */
    public static function latinDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
