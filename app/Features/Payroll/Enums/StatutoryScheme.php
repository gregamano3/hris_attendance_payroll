<?php

namespace App\Features\Payroll\Enums;

enum StatutoryScheme: string
{
    case Sss = 'sss';
    case PhilHealth = 'philhealth';
    case PagIbig = 'pagibig';

    public function label(): string
    {
        return match ($this) {
            self::Sss => 'SSS',
            self::PhilHealth => 'PhilHealth',
            self::PagIbig => 'Pag-IBIG',
        };
    }

    /**
     * Parameter keys with their descriptions, used for validation and forms.
     *
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return match ($this) {
            self::Sss => [
                'ee_rate' => 'Employee share (fraction of MSC)',
                'er_rate' => 'Employer share (fraction of MSC)',
                'msc_min' => 'Minimum MSC (₱)',
                'msc_max' => 'Maximum MSC (₱)',
                'msc_step' => 'MSC bracket width (₱)',
                'ec_threshold' => 'EC higher rate from MSC (₱)',
                'ec_low' => 'EC below threshold (₱)',
                'ec_high' => 'EC from threshold (₱)',
            ],
            self::PhilHealth => [
                'rate' => 'Premium rate (fraction)',
                'floor' => 'Salary floor (₱)',
                'ceiling' => 'Salary ceiling (₱)',
                'ee_share' => 'Employee share of premium (fraction)',
            ],
            self::PagIbig => [
                'ee_rate' => 'Employee rate (fraction)',
                'ee_rate_low' => 'Employee rate at/below threshold (fraction)',
                'low_threshold' => 'Low-income threshold (₱)',
                'er_rate' => 'Employer rate (fraction)',
                'max_base' => 'Maximum fund salary (₱)',
            ],
        };
    }
}
