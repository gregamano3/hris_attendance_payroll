@extends('layouts.app')

@section('title', $opening->title)

@section('page_actions')
    <a href="{{ route('recruitment.openings.edit', $opening) }}" class="btn btn-outline-primary">Edit</a>
    <form method="post" action="{{ route('recruitment.openings.toggle', $opening) }}" class="d-inline">
        @csrf @method('patch')
        <button class="btn btn-outline-secondary">{{ $opening->status === 'open' ? 'Close opening' : 'Reopen' }}</button>
    </form>
@stop

@section('page')
    <p class="text-body-secondary">
        <span class="badge text-bg-{{ $opening->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($opening->status) }}</span>
        {{ $opening->department?->name }} · {{ $opening->branch?->name }} · {{ $opening->employment_type->label() }} · {{ $opening->slots }} slot(s)
    </p>

    <div class="row g-2 mb-3" id="pipeline">
        @foreach ($columns as $stage => $applicants)
            @php $stageEnum = \App\Features\Recruitment\Enums\ApplicantStage::from($stage); @endphp
            <div class="col-md-4 col-xl-2">
                <div class="card h-100">
                    <div class="card-header py-2"><span class="badge text-bg-{{ $stageEnum->badge() }}">{{ $stageEnum->label() }}</span> <span class="small text-body-secondary">{{ $applicants->count() }}</span></div>
                    <ul class="list-group list-group-flush">
                        @forelse ($applicants as $applicant)
                            <li class="list-group-item small"><a href="{{ route('recruitment.applicants.show', $applicant) }}">{{ $applicant->fullName() }}</a>
                                <div class="text-body-secondary">{{ $applicant->stage_changed_at?->diffForHumans() }}</div></li>
                        @empty
                            <li class="list-group-item small text-body-secondary">—</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7">
            @if ($opening->description)
                <div class="card"><div class="card-body" style="white-space: pre-line">{{ $opening->description }}</div></div>
            @endif
        </div>
        <div class="col-lg-5">
            <form method="post" action="{{ route('recruitment.applicants.store', $opening) }}" enctype="multipart/form-data" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Add applicant</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="first_name" label="First name" col="col-6" required />
                    <x-form.input name="last_name" label="Last name" col="col-6" required />
                    <x-form.input name="email" label="Email" type="email" col="col-6" required />
                    <x-form.input name="mobile" label="Mobile" col="col-6" />
                    <x-form.input name="source" label="Source" col="col-12" placeholder="Referral, JobStreet, walk-in…" />
                    <div class="col-12">
                        <label for="resume" class="form-label">Résumé (PDF/DOC)</label>
                        <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" class="form-control @error('resume') is-invalid @enderror">
                        @error('resume') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>
@stop
