<?php

namespace App\Features\Payroll\Api;

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Payroll\Enums\PayrollRunStatus;
use App\Features\Payroll\Models\Payslip;
use App\Features\Payroll\Models\PayslipLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The caller's own payslips from finalized runs only.
 */
class PayslipsApiController
{
    public function __construct(private EmployeeDirectory $directory) {}

    public function index(Request $request): JsonResponse
    {
        $payslips = Payslip::query()
            ->with('run')
            ->where('employee_id', $this->employee($request)->id)
            ->whereHas('run', fn ($q) => $q->where('status', PayrollRunStatus::Finalized))
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->orderByDesc('payroll_runs.period_end')
            ->select('payslips.*')
            ->paginate(24);

        return response()->json([
            'data' => collect($payslips->items())->map(fn (Payslip $p) => $this->summary($p))->values(),
            'meta' => ['current_page' => $payslips->currentPage(), 'last_page' => $payslips->lastPage(), 'total' => $payslips->total()],
        ]);
    }

    public function show(Request $request, Payslip $payslip): JsonResponse
    {
        $payslip->load(['run', 'lines']);
        abort_unless($payslip->employee_id === $this->employee($request)->id && $payslip->run->status === PayrollRunStatus::Finalized, 404);

        return response()->json(['data' => [
            ...$this->summary($payslip),
            'taxable_income' => $payslip->taxable_income->toDecimal(),
            'employer_contributions' => $payslip->employer_contributions->toDecimal(),
            'attendance' => $payslip->attendance,
            'lines' => $payslip->lines->sortBy('sort')->map(fn (PayslipLine $line) => [
                'kind' => $line->kind,
                'code' => $line->code,
                'label' => $line->label,
                'quantity' => $line->quantity !== null ? (float) $line->quantity : null,
                'unit' => $line->unit,
                'amount' => $line->amount->toDecimal(),
                'taxable' => $line->taxable,
            ])->values(),
        ]]);
    }

    private function employee(Request $request): Employee
    {
        return $this->directory->forUser($request->user() ?? abort(401))
            ?? abort(403, 'Your account is not linked to an employee record.');
    }

    /**
     * Amounts are decimal strings in PHP pesos.
     *
     * @return array<string, mixed>
     */
    private function summary(Payslip $payslip): array
    {
        return [
            'id' => $payslip->id,
            'period_start' => $payslip->run->period_start->toDateString(),
            'period_end' => $payslip->run->period_end->toDateString(),
            'pay_date' => $payslip->run->pay_date->toDateString(),
            'run_type' => $payslip->run->type->value,
            'currency' => 'PHP',
            'gross_pay' => $payslip->gross_pay->toDecimal(),
            'total_deductions' => $payslip->total_deductions->toDecimal(),
            'net_pay' => $payslip->net_pay->toDecimal(),
        ];
    }
}
