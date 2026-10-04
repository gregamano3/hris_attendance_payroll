@php use App\Features\Recruitment\Enums\ApplicantStage; @endphp
@extends('layouts.app')

@section('title', $applicant->fullName())

@section('page_actions')
    <a href="{{ route('recruitment.openings.show', $applicant->opening) }}" class="btn btn-outline-secondary">Back to {{ $applicant->opening->title }}</a>
@stop

@section('page')
    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Stage</span><span class="badge text-bg-{{ $applicant->stage->badge() }}">{{ $applicant->stage->label() }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Email</span><strong>{{ $applicant->email }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Mobile</span><strong>{{ $applicant->mobile ?? '—' }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Source</span><strong>{{ $applicant->source ?? '—' }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Résumé</span>
                        @if ($applicant->resume_path)<a href="{{ route('recruitment.applicants.resume', $applicant) }}">{{ $applicant->resume_name }}</a>@else — @endif</li>
                    @if ($applicant->employee)
                        <li class="list-group-item d-flex justify-content-between"><span>Employee</span><a href="{{ route('employees.show', $applicant->employee) }}">{{ $applicant->employee->employee_no }}</a></li>
                    @endif
                </ul>
            </div>

            @unless ($applicant->stage === ApplicantStage::Hired)
                <form method="post" action="{{ route('recruitment.applicants.move', $applicant) }}" class="card">
                    @csrf @method('patch')
                    <div class="card-header"><h3 class="card-title">Move</h3></div>
                    <div class="card-body row g-2">
                        <x-form.select name="stage" label="Stage" :options="$stages" :value="$applicant->stage" col="col-12" />
                        <x-form.textarea name="note" label="Note" rows="2" />
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-primary">Update stage</button></div>
                </form>

                @if ($applicant->stage === ApplicantStage::Offer)
                    <form method="post" action="{{ route('recruitment.applicants.hire', $applicant) }}" class="card card-success card-outline">
                        @csrf
                        <div class="card-header"><h3 class="card-title">Hire</h3></div>
                        <div class="card-body row g-2">
                            <x-form.input name="employee_no" label="Employee no." col="col-6" required />
                            <x-form.input name="hired_at" label="Start date" type="date" :value="today()->toDateString()" col="col-6" required />
                            <x-form.select name="rate_type" label="Rate type" :options="['monthly' => 'Monthly', 'daily' => 'Daily']" col="col-6" />
                            <x-form.input name="basic_rate" label="Basic rate (₱)" type="number" step="0.01" min="0" col="col-6" required />
                        </div>
                        <div class="card-footer"><button class="btn btn-success" id="hire-button">Hire and create employee</button></div>
                    </form>
                @endif
            @endunless
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Activity</h3></div>
                <ul class="list-group list-group-flush">
                    @foreach ($applicant->events as $event)
                        <li class="list-group-item small">
                            <span class="text-body-secondary">{{ $event->created_at->format('M j, Y g:i A') }} · {{ $event->user?->name ?? 'System' }}</span><br>
                            @if ($event->to_stage)<strong>{{ $event->from_stage ? ucfirst($event->from_stage).' → ' : '' }}{{ ucfirst($event->to_stage) }}</strong>@endif
                            {{ $event->note }}
                        </li>
                    @endforeach
                </ul>
                <form method="post" action="{{ route('recruitment.applicants.note', $applicant) }}" class="card-footer d-flex gap-2">
                    @csrf
                    <input name="note" class="form-control form-control-sm" placeholder="Add a note (interview feedback, references…)" aria-label="Note" required>
                    <button class="btn btn-sm btn-outline-secondary">Add</button>
                </form>
            </div>
        </div>
    </div>
@stop
