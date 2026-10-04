<?php

namespace App\Features\Payroll\Models;

use App\Features\Employees\Models\Employee;
use App\Shared\Money\Money;
use App\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $payroll_run_id
 * @property int $employee_id
 * @property string $employee_no
 * @property string $employee_name
 * @property string|null $department
 * @property string|null $position
 * @property string $rate_type
 * @property Money $basic_rate
 * @property Money $daily_rate
 * @property Money $hourly_rate
 * @property bool $is_minimum_wage_earner
 * @property Money $gross_pay
 * @property Money $taxable_income
 * @property Money $total_deductions
 * @property Money $net_pay
 * @property Money $employer_contributions
 * @property array<string, int|float> $attendance
 * @property list<string>|null $warnings
 * @property-read PayrollRun $run
 * @property-read Employee $employee
 * @property-read Collection<int, PayslipLine> $lines
 */
#[Fillable([
    'payroll_run_id', 'employee_id', 'employee_no', 'employee_name', 'department', 'position', 'rate_type',
    'basic_rate', 'daily_rate', 'hourly_rate', 'is_minimum_wage_earner', 'gross_pay', 'taxable_income', 'total_deductions', 'net_pay',
    'employer_contributions', 'attendance', 'warnings',
])]
class Payslip extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'basic_rate' => MoneyCast::class,
            'daily_rate' => MoneyCast::class,
            'hourly_rate' => MoneyCast::class,
            'gross_pay' => MoneyCast::class,
            'taxable_income' => MoneyCast::class,
            'total_deductions' => MoneyCast::class,
            'net_pay' => MoneyCast::class,
            'employer_contributions' => MoneyCast::class,
            'is_minimum_wage_earner' => 'boolean',
            'attendance' => 'array',
            'warnings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return HasMany<PayslipLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class)->orderBy('sort');
    }

    public function amountOf(string $code): Money
    {
        return Money::ofCentavos((int) $this->lines->where('code', $code)->sum(fn (PayslipLine $line) => $line->amount->centavos));
    }
}
