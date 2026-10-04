<?php

namespace App\Features\Analytics\ShowAnalytics;

use App\Shared\Xlsx;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowAnalyticsController
{
    public function show(HrAnalytics $analytics): View
    {
        return view('analytics::index', ['data' => $analytics->snapshot()]);
    }

    public function export(HrAnalytics $analytics): StreamedResponse
    {
        $data = $analytics->snapshot();

        return Xlsx::download('hr-analytics-'.today()->format('Ymd').'.xlsx',
            ['Month', 'Hires', 'Separations', 'Headcount', 'Turnover %', 'Payroll gross', 'Employer share'],
            array_map(fn (array $m, array $p) => [
                $m['month'], $m['hires'], $m['separations'], $m['headcount'], $m['turnover'], $p['gross'], $p['employer'],
            ], $data['movement'], $data['payroll_cost']),
            'Monthly',
        );
    }
}
