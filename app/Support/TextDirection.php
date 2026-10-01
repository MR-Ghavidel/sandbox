<?php

namespace App\Support;

/**
 * Decides whether a piece of text reads right-to-left or left-to-right.
 *
 * The browser's own detection (dir="auto") looks only at the first letter, so a Persian sentence
 * that starts with "Laravel" turns left-to-right. Here the script with more words wins, and a tie
 * goes to right-to-left because the app is Persian.
 */
class TextDirection
{
    public const RTL = 'rtl';

    public const LTR = 'ltr';

    /**
     * @return self::RTL|self::LTR
     */
    public static function detect(?string $text, string $default = self::RTL): string
    {
        preg_match_all('/\p{L}[\p{L}\p{M}]*/u', (string) $text, $matches);

        $rtlWords = 0;
        $ltrWords = 0;

        foreach ($matches[0] as $word) {
            preg_match('/^[\p{Arabic}\p{Hebrew}]/u', $word) === 1 ? $rtlWords++ : $ltrWords++;
        }

        return match (true) {
            $rtlWords === 0 && $ltrWords === 0 => $default,
            $rtlWords >= $ltrWords => self::RTL,
            default => self::LTR,
        };
    }
}
