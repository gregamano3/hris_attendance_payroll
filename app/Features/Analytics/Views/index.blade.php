@extends('layouts.app')

@section('title', 'HR analytics')

@section('page_actions')
    <a href="{{ route('analytics.export') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
@stop

@section('page')
    <div class="row">
        @foreach ([
            ['Headcount', number_format($data['headcount']), 'bi-people', 'primary'],
            ['Attendance rate (30 days)', $data['attendance']['attendance_rate'].'%', 'bi-calendar-check', 'success'],
            ['Late arrivals (30 days)', $data['attendance']['late_rate'].'% of present days', 'bi-alarm', 'warning'],
            ['Turnover (12 months)', number_format(collect($data['movement'])->sum('separations')).' separations', 'bi-box-arrow-right', 'danger'],
        ] as [$label, $value, $icon, $theme])
            <div class="col-md-3 col-6">
                <div class="info-box"><span class="info-box-icon text-bg-{{ $theme }}"><i class="bi {{ $icon }}"></i></span>
                    <div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $value }}</span></div></div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Headcount by department</h3></div>
            <div class="card-body"><canvas id="chart-department" height="220" aria-label="Headcount by department" role="img"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Hires and separations</h3></div>
            <div class="card-body"><canvas id="chart-movement" height="220" aria-label="Hires and separations per month" role="img"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Payroll cost (finalized)</h3></div>
            <div class="card-body"><canvas id="chart-payroll" height="220" aria-label="Payroll cost per month" role="img"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Headcount by branch and type</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="headcount-table">
                    @foreach ($data['by_branch'] as $label => $total)<tr><td>{{ $label }}</td><td class="text-end">{{ $total }}</td></tr>@endforeach
                    @foreach ($data['by_type'] as $label => $total)<tr class="table-light"><td>{{ ucfirst($label) }}</td><td class="text-end">{{ $total }}</td></tr>@endforeach
                </table>
            </div></div></div>
    </div>

    <script type="application/json" id="analytics-data">@json($data)</script>
@stop

@push('js')
    @vite('resources/js/analytics.js')
@endpush
