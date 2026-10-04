<?php

namespace App\Features\Employees\Enums;

/**
 * Philippine government identification numbers stored as digits only.
 */
enum GovernmentId: string
{
    case Sss = 'sss_no';
    case PhilHealth = 'philhealth_no';
    case PagIbig = 'pagibig_no';
    case Tin = 'tin';

    public function label(): string
    {
        return match ($this) {
            self::Sss => 'SSS No.',
            self::PhilHealth => 'PhilHealth No.',
            self::PagIbig => 'Pag-IBIG MID No.',
            self::Tin => 'TIN',
        };
    }

    /**
     * Accepted digit counts.
     *
     * @return list<int>
     */
    public function lengths(): array
    {
        return match ($this) {
            self::Sss => [10],
            self::PhilHealth, self::PagIbig => [12],
            self::Tin => [9, 12],
        };
    }

    /**
     * Group sizes used for display, e.g. SSS 34-1234567-8.
     *
     * @return list<int>
     */
    private function groups(): array
    {
        return match ($this) {
            self::Sss => [2, 7, 1],
            self::PhilHealth => [2, 9, 1],
            self::PagIbig => [4, 4, 4],
            self::Tin => [3, 3, 3, 3],
        };
    }

    /**
     * Column holding the HMAC blind index of the encrypted value.
     */
    public function blindIndexColumn(): string
    {
        return $this->value.'_bidx';
    }

    public function placeholder(): string
    {
        return $this->format(str_repeat('0', max($this->lengths())));
    }

    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    public function format(?string $digits): string
    {
        if ($digits === null || $digits === '') {
            return '';
        }

        $parts = [];
        $offset = 0;

        foreach ($this->groups() as $size) {
            if ($offset >= strlen($digits)) {
                break;
            }

            $parts[] = substr($digits, $offset, $size);
            $offset += $size;
        }

        return implode('-', $parts);
    }
}
