@php use App\Features\Payroll\Enums\PayrollRunStatus; @endphp
@extends('layouts.app')

@section('title', $run->name)

@section('page_actions')
    <div class="d-flex flex-wrap gap-2">
        @if ($run->payslips()->exists())
            <div class="btn-group">
                <a href="{{ route('payroll.runs.register', $run) }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i> Register</a>
                <a href="{{ route('payroll.runs.register.xlsx', $run) }}" class="btn btn-outline-secondary" title="Excel"><i class="bi bi-file-earmark-excel"></i></a>
            </div>
        @endif
        @if ($run->isLocked())
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-bank me-1"></i> Bank file</button>
                <ul class="dropdown-menu">
                    @foreach (\App\Features\Payroll\ExportBankFile\ExportBankFileController::formats() as $format)
                        <li><a class="dropdown-item" href="{{ route('payroll.runs.bank', [$run, $format->key()]) }}">{{ $format->label() }} (.{{ $format->extension() }})</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
        @unless ($run->isLocked() || $run->isComputing())
            @can('payroll.manage')
                <form method="post" action="{{ route('payroll.runs.compute', $run) }}" onsubmit="this.querySelector('button').disabled = true">
                    @csrf
                    <button class="btn btn-primary" id="compute-button"><i class="bi bi-calculator me-1"></i> {{ $run->computed_at ? 'Recompute' : 'Compute' }}</button>
                </form>
            @endcan
            @can('payroll.finalize')
                @if ($run->status === PayrollRunStatus::Computed)
                    <form method="post" action="{{ route('payroll.runs.finalize', $run) }}" data-confirm="Finalize this payroll? It can no longer be changed.">
                        @csrf
                        <button class="btn btn-success" id="finalize-button"><i class="bi bi-lock me-1"></i> Finalize</button>
                    </form>
                @endif
            @endcan
            @can('payroll.manage')
                <x-delete-button :action="route('payroll.runs.destroy', $run)" label="Delete" class="btn-md" confirm="Delete this payroll run?" />
            @endcan
        @endunless
    </div>
@stop

@section('page')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-{{ $run->status->badge() }}"><i class="bi bi-flag"></i></span>
                <div class="info-box-content"><span class="info-box-text">Status</span><span class="info-box-number">{{ $run->status->label() }}</span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-cash"></i></span>
                <div class="info-box-content"><span class="info-box-text">Gross pay</span><span class="info-box-number">{{ $run->total_gross->format() }}</span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-wallet2"></i></span>
                <div class="info-box-content"><span class="info-box-text">Net pay</span><span class="info-box-number">{{ $run->total_net->format() }}</span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-building"></i></span>
                <div class="info-box-content"><span class="info-box-text">Employer share</span><span class="info-box-number">{{ $run->total_employer->format() }}</span></div></div>
        </div>
    </div>

    <p class="text-body-secondary">
        <span class="badge text-bg-light border">{{ $run->type->label() }}</span>
        @if ($run->annualize_tax)<span class="badge text-bg-warning">Year-end annualization</span>@endif
        Period {{ $run->period()->label() }} · Pay date {{ $run->pay_date->format('M j, Y') }}
        @if ($run->finalized_at) · Finalized {{ $run->finalized_at->format('M j, Y g:i A') }} by {{ $run->finalizer?->name }} @endif
    </p>

    @if ($run->isComputing())
        <div class="card card-body mb-3" id="compute-progress" data-status-url="{{ route('payroll.runs.status', $run) }}">
            <div class="mb-1">Computing payslips… this page refreshes automatically.</div>
            <div class="progress" role="progressbar" aria-label="Computation progress" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: {{ max(5, $run->progress) }}%">{{ $run->progress }}%</div>
            </div>
        </div>
        @push('js')
            <script>
                (function poll() {
                    const box = document.getElementById('compute-progress');
                    fetch(box.dataset.statusUrl, { headers: { Accept: 'application/json' } })
                        .then((r) => r.json())
                        .then((data) => {
                            if (data.status !== 'computing') return window.location.reload();
                            const bar = box.querySelector('.progress-bar');
                            bar.style.width = Math.max(5, data.progress) + '%';
                            bar.textContent = data.progress + '%';
                            setTimeout(poll, 2000);
                        })
                        .catch(() => setTimeout(poll, 5000));
                })();
            </script>
        @endpush
    @endif
    @if ($run->compute_error)
        <div class="alert alert-danger">The last computation failed: {{ $run->compute_error }}</div>
    @endif

    @if ($run->status === PayrollRunStatus::Draft && $run->computed_at)
        <div class="alert alert-warning">Adjustments changed since the last computation. Recompute before finalizing.</div>
    @endif
    @if ($run->period_end->gte(today()) && ! $run->isLocked())
        <div class="alert alert-info">The period has not ended yet. Days still to come are not paid or deducted until you recompute.</div>
    @endif

    @if ($withoutBank->isNotEmpty())
        <div class="alert alert-warning">
            <strong>{{ $withoutBank->count() }} employee(s) have no bank account</strong> and are not in the bank file; pay them by cash or check:
            {{ $withoutBank->map(fn ($p) => $p->employee_name.' ('.$p->net_pay->format().')')->implode(', ') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">Payslips</h3></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-hover table-striped mb-0 align-middle" id="payslips-table">
                <thead>
                    <tr><th>Employee</th><th>Department</th><th class="text-end">Days worked</th><th class="text-end">Absent</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                        <tr>
                            <td>{{ $payslip->employee_name }} <span class="small text-body-secondary">{{ $payslip->employee_no }}</span>
                                @if ($payslip->warnings)<i class="bi bi-exclamation-triangle text-warning" title="{{ implode(' ', $payslip->warnings) }}"></i>@endif
                            </td>
                            <td>{{ $payslip->department ?? '—' }}</td>
                            <td class="text-end">{{ $payslip->attendance['days_worked'] ?? 0 }}</td>
                            <td class="text-end">{{ $payslip->attendance['days_absent'] ?? 0 }}</td>
                            <td class="text-end">{{ $payslip->gross_pay->format(false) }}</td>
                            <td class="text-end">{{ $payslip->total_deductions->format(false) }}</td>
                            <td class="text-end fw-semibold">{{ $payslip->net_pay->format(false) }}</td>
                            <td class="actions"><a href="{{ route('payroll.payslips.show', $payslip) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">No payslips yet. Compute the run to generate them.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($costSummary->count() > 1)
        <div class="card">
            <div class="card-header"><h3 class="card-title">By branch and cost center</h3></div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0" id="cost-summary">
                    <thead><tr><th>Branch · cost center</th><th class="text-end">Employees</th><th class="text-end">Gross</th><th class="text-end">Net</th><th class="text-end">Employer share</th><th class="text-end">Total cost</th></tr></thead>
                    <tbody>
                        @foreach ($costSummary as $group => $totals)
                            <tr>
                                <td>{{ $group }}</td>
                                <td class="text-end">{{ $totals['count'] }}</td>
                                <td class="text-end">{{ $totals['gross']->format(false) }}</td>
                                <td class="text-end">{{ $totals['net']->format(false) }}</td>
                                <td class="text-end">{{ $totals['employer']->format(false) }}</td>
                                <td class="text-end fw-semibold">{{ $totals['gross']->plus($totals['employer'])->format(false) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @unless ($run->isThirteenthMonth())
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Adjustments</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th>Employee</th><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @forelse ($adjustments as $adjustment)
                                <tr>
                                    <td>{{ $adjustment->employee->full_name }}</td>
                                    <td>{{ $adjustment->kind === 'earning' ? ($adjustment->taxable ? 'Taxable earning' : 'Non-taxable earning') : 'Deduction' }}</td>
                                    <td>{{ $adjustment->label }}</td>
                                    <td class="text-end">{{ $adjustment->amount->format() }}</td>
                                    <td class="actions">
                                        @if (! $run->isLocked() && auth()->user()->can('payroll.manage'))
                                            <x-delete-button :action="route('payroll.runs.adjustments.destroy', [$run, $adjustment])" label="Remove" confirm="Remove this adjustment?" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-3">No adjustments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if (! $run->isLocked() && auth()->user()->can('payroll.manage'))
            <div class="col-lg-4">
                <form method="post" action="{{ route('payroll.runs.adjustments.store', $run) }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Add adjustment</h3></div>
                    <div class="card-body row g-3">
                        <x-form.select name="employee_id" label="Employee" :options="$employees" col="col-12" placeholder="Select…" required />
                        <x-form.select name="kind" label="Type" :options="['earning' => 'Earning (allowance, bonus…)', 'deduction' => 'Deduction (loan, cash advance…)']" col="col-12" required />
                        <x-form.input name="label" label="Description" col="col-12" required />
                        <x-form.input name="amount" label="Amount (₱)" type="number" step="0.01" min="0" col="col-12" required />
                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="taxable" value="0">
                                <input class="form-check-input" type="checkbox" id="taxable" name="taxable" value="1" checked>
                                <label class="form-check-label" for="taxable">Taxable (earnings only)</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Add</button></div>
                </form>

                <form method="post" action="{{ route('payroll.runs.service-charge', $run) }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Service charge distribution</h3></div>
                    <div class="card-body row g-3">
                        <p class="small text-body-secondary mb-0">RA 11360: service charges collected are shared equally by the employees of this run (replaces a previous distribution).</p>
                        <x-form.input name="amount" id="service_charge_amount" label="Total collected (₱)" type="number" step="0.01" min="0" col="col-12" required />
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-primary">Distribute</button></div>
                </form>

                <form method="post" action="{{ route('payroll.runs.back-pay', $run) }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Back pay</h3></div>
                    <div class="card-body row g-3">
                        <p class="small text-body-secondary mb-0">After a retroactive salary change, adds the difference for every finalized run since the date.</p>
                        <x-form.select name="employee_id" id="back_pay_employee" label="Employee" :options="$employees" col="col-12" placeholder="Select…" required />
                        <x-form.input name="since" id="back_pay_since" label="Change effective from" type="date" col="col-12" required />
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-primary">Compute back pay</button></div>
                </form>
            </div>
        @endif
    </div>
    @endunless
@stop
