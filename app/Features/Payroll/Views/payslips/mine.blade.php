@extends('layouts.app')

@section('title', 'My payslips')

@section('page')
    @if (! $employee)
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    @else
        <div class="card">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead><tr><th>Period</th><th>Pay date</th><th class="text-end">Gross</th><th class="text-end">Net pay</th><th class="actions"></th></tr></thead>
                    <tbody>
                        @forelse ($payslips as $payslip)
                            <tr>
                                <td>{{ $payslip->run->period()->label() }}</td>
                                <td>{{ $payslip->run->pay_date->format('M j, Y') }}</td>
                                <td class="text-end">{{ $payslip->gross_pay->format() }}</td>
                                <td class="text-end fw-semibold">{{ $payslip->net_pay->format() }}</td>
                                <td class="actions">
                                    <a href="{{ route('payroll.payslips.show', $payslip) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('payroll.payslips.pdf', $payslip) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-body-secondary py-4">No payslips yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payslips->hasPages())
                <div class="card-footer">{{ $payslips->links() }}</div>
            @endif
        </div>
    @endif
@stop
