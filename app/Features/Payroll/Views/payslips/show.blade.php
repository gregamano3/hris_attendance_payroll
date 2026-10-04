@extends('layouts.app')

@section('title', 'Payslip')

@section('page_actions')
    <a href="{{ route('payroll.payslips.pdf', $payslip) }}" class="btn btn-outline-primary"><i class="bi bi-filetype-pdf me-1"></i> Download PDF</a>
    @can('payroll.view')
        <a href="{{ route('payroll.runs.show', $payslip->run) }}" class="btn btn-outline-secondary">Back to run</a>
    @endcan
@stop

@push('css')
    <style>
        .payslip-lines { margin-top: 1rem; border-collapse: collapse; }
        .payslip-lines th { background: var(--bs-tertiary-bg); padding: .4rem .5rem; text-transform: uppercase; font-size: .8rem; }
        .payslip-lines td { padding: .3rem .5rem; border-bottom: 1px solid var(--bs-border-color); }
        .payslip-lines .amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .payslip-lines .qty { text-align: right; color: var(--bs-secondary-color); white-space: nowrap; }
        .payslip-lines .total td { font-weight: 600; }
        .payslip-lines .net td { font-weight: 700; font-size: 1.15rem; border-bottom: 0; }
    </style>
@endpush

@section('page')
    @if ($payslip->warnings)
        <div class="alert alert-warning">{{ implode(' ', $payslip->warnings) }}</div>
    @endif
    @unless ($payslip->run->isLocked())
        <div class="alert alert-info">Draft: this payslip may still change until the payroll is finalized.</div>
    @endunless
    <div class="card"><div class="card-body">@include('payroll::payslips._body')</div></div>
@stop
