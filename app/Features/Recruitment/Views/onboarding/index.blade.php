@extends('layouts.app')

@section('title', 'Onboarding')

@section('page')
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">New hires</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th>Employee</th><th>Start</th><th style="width: 40%">Progress</th></tr></thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                @php $pct = $employee->onboarding_tasks_count ? round($employee->done_count / $employee->onboarding_tasks_count * 100) : 0; @endphp
                                <tr>
                                    <td><a href="{{ route('recruitment.onboarding.show', $employee) }}">{{ $employee->full_name }}</a></td>
                                    <td>{{ $employee->hired_at->format('M j, Y') }}</td>
                                    <td><div class="progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $pct }}%">{{ $employee->done_count }}/{{ $employee->onboarding_tasks_count }}</div></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-3">No onboarding in progress.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Templates</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($templates as $template)
                        <li class="list-group-item">{{ $template->name }} @if ($template->is_default)<span class="badge text-bg-primary">Default</span>@endif
                            <div class="small text-body-secondary">{{ collect($template->items)->pluck('title')->implode(' · ') }}</div></li>
                    @empty
                        <li class="list-group-item text-body-secondary">No templates yet.</li>
                    @endforelse
                </ul>
            </div>
            <form method="post" action="{{ route('recruitment.onboarding.templates.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">New template</h3></div>
                <div class="card-body row g-2">
                    <x-form.input name="name" label="Name" col="col-12" required />
                    <x-form.textarea name="items" label="Tasks (one per line, optional “| days after start”)" rows="7" required
                        value="Sign employment contract | 0&#10;Submit SSS, PhilHealth, Pag-IBIG and TIN numbers | 0&#10;Submit NBI clearance and medical | 7&#10;Create user account and email | 0&#10;Issue ID and equipment | 1&#10;Orientation and data privacy briefing | 1&#10;Enroll bank payroll account | 7" />
                    <div class="col-12 form-check ms-2">
                        <input type="hidden" name="is_default" value="0">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                        <label class="form-check-label" for="is_default">Default for new hires</label>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Save template</button></div>
            </form>
        </div>
    </div>
@stop
