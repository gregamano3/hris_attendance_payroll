<?php

namespace App\Features\Payroll\ShowPayslip;

use App\Features\Payroll\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ShowPayslipController
{
    public function show(Request $request, Payslip $payslip): View
    {
        $this->authorize($request, $payslip);

        return view('payroll::payslips.show', ['payslip' => $payslip->load(['run', 'lines'])]);
    }

    public function pdf(Request $request, Payslip $payslip): Response
    {
        $this->authorize($request, $payslip);
        $payslip->load(['run', 'lines']);

        return Pdf::loadView('payroll::payslips.pdf', compact('payslip'))
            ->setPaper('a5', 'portrait')
            ->download(sprintf('payslip-%s-%s.pdf', $payslip->employee_no, $payslip->run->period_end->format('Ymd')));
    }

    /**
     * Payroll staff see every payslip; employees only their own finalized ones.
     */
    private function authorize(Request $request, Payslip $payslip): void
    {
        $user = $request->user();

        if ($user?->can('payroll.view')) {
            return;
        }

        abort_unless(
            $user?->can('payslips.view-own')
                && $payslip->employee->user_id === $user->id
                && $payslip->run->isLocked(),
            403,
        );
    }
}
