<?php

namespace App\Shared;

use App\Shared\Money\Money;
use Illuminate\Support\Str;

/**
 * Helpers for fixed-width text files (government and bank uploads).
 */
final class FixedWidth
{
    /** Left-aligned, upper-cased ASCII text padded or cut to the width. */
    public static function text(?string $value, int $width): string
    {
        $ascii = Str::upper(Str::ascii((string) $value));

        return str_pad(mb_substr($ascii, 0, $width), $width);
    }

    /** Right-aligned digits padded with zeros. */
    public static function digits(?string $value, int $width): string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return str_pad(substr($digits, -$width), $width, '0', STR_PAD_LEFT);
    }

    /** Amount in centavos (implied 2 decimals), zero-padded. */
    public static function amount(Money $amount, int $width): string
    {
        return str_pad((string) max(0, $amount->centavos), $width, '0', STR_PAD_LEFT);
    }
}
