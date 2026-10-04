<?php

namespace App\Shared;

final class Format
{
    /**
     * 0 => "—", 65 => "1:05"
     */
    public static function minutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function hours(int $minutes): string
    {
        return number_format($minutes / 60, 2);
    }
}
