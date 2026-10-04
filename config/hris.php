<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default administrator
    |--------------------------------------------------------------------------
    |
    | Credentials of the administrator created by the database seeder.
    | Change the password right after the first login.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    // Login POSTs allowed per minute and IP address (failed attempts are
    // additionally limited to 5 per account).
    'login_throttle_per_minute' => (int) (env('LOGIN_THROTTLE') ?: 10),

    /*
    | Employer details printed on government e-filing files and bank files.
    */
    'employer' => [
        'name' => env('EMPLOYER_NAME', env('APP_NAME', 'Company')),
        'address' => env('EMPLOYER_ADDRESS', ''),
        'tin' => env('EMPLOYER_TIN', ''),            // 9 digits
        'tin_branch' => env('EMPLOYER_TIN_BRANCH', '0000'),
        'rdo' => env('EMPLOYER_RDO', ''),
        'sss_no' => env('EMPLOYER_SSS_NO', ''),      // employer SSS number
        'philhealth_no' => env('EMPLOYER_PHILHEALTH_NO', ''),
        'pagibig_no' => env('EMPLOYER_PAGIBIG_NO', ''),
        'bank_company_code' => env('EMPLOYER_BANK_COMPANY_CODE', ''),
        'bank_account_no' => env('EMPLOYER_BANK_ACCOUNT_NO', ''),
    ],

    'privacy' => [
        // Years after separation before personal data is anonymized
        // (privacy:anonymize, monthly). Keep at least the longest legal
        // retention period that applies to you (e.g. BIR books of accounts).
        'retention_years' => (int) env('PRIVACY_RETENTION_YEARS', 10),
        'dpo_email' => env('PRIVACY_DPO_EMAIL', ''),
        // Months after rejection before applicant data and résumés are deleted.
        'applicant_retention_months' => (int) env('PRIVACY_APPLICANT_RETENTION_MONTHS', 12),
    ],

    /*
    | REST API (Sanctum personal access tokens). See docs/api.md.
    */
    'api' => [
        'enabled' => (bool) env('API_ENABLED', true),
        'rate_limit_per_minute' => (int) env('API_RATE_LIMIT', 60),
        'max_token_days' => (int) env('API_MAX_TOKEN_DAYS', 365),
    ],

    'security' => [
        // Key for the blind indexes of encrypted columns (government IDs).
        // Generate with: php -r "echo bin2hex(random_bytes(32));"
        // Keep it stable: changing it requires `php artisan security:reencrypt`.
        'blind_index_key' => env('BLIND_INDEX_KEY', ''),
    ],

    // Comma separated roles that must enable two-factor authentication,
    // e.g. "admin,payroll". Leave empty to keep 2FA optional for everyone.
    'require_two_factor_roles' => env('REQUIRE_2FA_ROLES', ''),

    /*
    |--------------------------------------------------------------------------
    | Attendance rules
    |--------------------------------------------------------------------------
    */

    'attendance' => [
        // Overtime shorter than this is ignored.
        'overtime_threshold_minutes' => (int) env('ATTENDANCE_OT_THRESHOLD', 30),

        // Only approved overtime (capped at the approved minutes) is paid.
        'overtime_requires_approval' => (bool) env('ATTENDANCE_OT_REQUIRES_APPROVAL', true),

        // Night differential window (Labor Code Art. 86: 10 PM to 6 AM).
        'night_diff_start_hour' => 22,
        'night_diff_end_hour' => 6,

        // Punches up to this many minutes before shift start / after shift end
        // belong to that shift's day.
        'log_window_before_minutes' => 240,
        'log_window_after_minutes' => 480,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payroll rules
    |--------------------------------------------------------------------------
    |
    | Premium multipliers follow the Labor Code / DOLE handbook. The amounts
    | of statutory contributions and taxes live in the database (Payroll >
    | Statutory rates) so they can be updated without a release.
    |
    */

    'payroll' => [
        // Divisor used to derive the daily rate of monthly-paid employees
        // (261 = 5-day work week, 313 = 6-day work week).
        'days_per_year' => (int) env('PAYROLL_DAYS_PER_YEAR', 261),
        'hours_per_day' => 8,

        // Share of the monthly contributions deducted on each payroll run
        // (0.5 = split evenly between the two semi-monthly cutoffs).
        'contribution_fraction' => 0.5,

        // Withholding tax table used for each run.
        'tax_frequency' => 'semi_monthly',

        // Pay for hours worked, as a multiple of the hourly rate.
        'multipliers' => [
            'regular' => 1.00,
            'rest_day' => 1.30,
            'special' => 1.30,
            'special_rest' => 1.50,
            'regular_holiday' => 2.00,
            'regular_holiday_rest' => 2.60,
        ],
        'overtime_regular' => 1.25,   // OT on an ordinary day
        'overtime_premium' => 1.30,   // OT on rest days / holidays: day rate x 130%
        'night_differential' => 0.10, // +10% of the applicable hourly rate

        // Unworked regular holidays are paid only when the employee was
        // present (or on paid leave) on the preceding work day.
        'holiday_eligibility' => (bool) env('PAYROLL_HOLIDAY_ELIGIBILITY', true),

        // 13th month pay and other benefits are tax-exempt up to this amount
        // per year (TRAIN law); the excess is taxable.
        'thirteenth_month_exempt_ceiling' => (int) env('PAYROLL_13TH_MONTH_EXEMPT_CEILING', 90000),

        // Payslip lines counted as "basic salary" for the 13th month pay
        // (overtime, premiums, night differential, holiday pay and allowances
        // are excluded per the PD 851 rules).
        'thirteenth_month_basic_codes' => ['BASIC', 'ABSENCES', 'TARDINESS', 'PAID_LEAVE'],
    ],

];
