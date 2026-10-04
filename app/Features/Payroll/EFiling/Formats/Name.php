<?php

namespace App\Features\Payroll\EFiling\Formats;

use Illuminate\Support\Str;

final class Name
{
    /** Upper-case ASCII (agency systems reject accented letters): "José" → "JOSE". */
    public static function upper(string $value): string
    {
        return Str::upper(Str::ascii($value));
    }
}
