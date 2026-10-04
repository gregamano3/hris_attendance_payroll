<?php

namespace App\Features\Payroll\Compute;

use App\Shared\Money\Money;

/**
 * PhilHealth premium: rate × monthly basic salary bounded by a floor and a
 * ceiling, shared between employee and employer.
 *
 * Parameters: rate, floor, ceiling, ee_share (fraction paid by the employee)
 */
final readonly class PhilHealthCalculator
{
    /**
     * @param  array<string, int|float|string>  $parameters
     */
    public function __construct(private array $parameters) {}

    /**
     * @return array{premium: Money, employee: Money, employer: Money}
     */
    public function monthly(Money $basicSalary): array
    {
        $base = $basicSalary
            ->max(Money::ofPesos($this->parameters['floor']))
            ->min(Money::ofPesos($this->parameters['ceiling']));

        $premium = $base->multipliedBy($this->parameters['rate']);
        $employee = $premium->multipliedBy($this->parameters['ee_share']);

        return [
            'premium' => $premium,
            'employee' => $employee,
            'employer' => $premium->minus($employee),
        ];
    }
}
