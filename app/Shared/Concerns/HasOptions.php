<?php

namespace App\Shared\Concerns;

/**
 * For backed enums exposing a label(): builds value => label select options.
 */
trait HasOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
