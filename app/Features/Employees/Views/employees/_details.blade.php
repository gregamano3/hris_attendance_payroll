@php use App\Features\Employees\Enums\GovernmentId; @endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card card-primary card-outline">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-3 text-body-secondary"></i>
                <h3 class="fs-4 mt-2 mb-0">{{ $employee->first_name }} {{ $employee->last_name }} {{ $employee->suffix }}</h3>
                <p class="text-body-secondary mb-2">{{ $employee->position->title ?? 'No position' }}</p>
                <span class="badge text-bg-{{ $employee->status->badge() }}">{{ $employee->status->label() }}</span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>Employee no.</span><strong>{{ $employee->employee_no }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Department</span><strong>{{ $employee->department->name ?? '—' }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Type</span><strong>{{ $employee->employment_type->label() }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Hired</span><strong>{{ $employee->hired_at->format('M d, Y') }}</strong></li>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Personal information</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Full name</dt><dd class="col-sm-8">{{ $employee->full_name }}</dd>
                    <dt class="col-sm-4">Birth date</dt><dd class="col-sm-8">{{ $employee->birth_date?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="col-sm-4">Gender</dt><dd class="col-sm-8">{{ $employee->gender?->label() ?? '—' }}</dd>
                    <dt class="col-sm-4">Civil status</dt><dd class="col-sm-8">{{ $employee->civil_status?->label() ?? '—' }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $employee->email ?? '—' }}</dd>
                    <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8">{{ $employee->mobile ?? '—' }}</dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $employee->address ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Employment &amp; compensation</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Regularized</dt><dd class="col-sm-8">{{ $employee->regularized_at?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="col-sm-4">Separated</dt><dd class="col-sm-8">{{ $employee->separated_at?->format('M d, Y') ?? '—' }}</dd>
                    <dt class="col-sm-4">Basic rate</dt><dd class="col-sm-8">{{ $employee->basic_rate->format() }} / {{ $employee->rate_type->unit() }}
                        @if ($employee->is_minimum_wage_earner)<span class="badge text-bg-info">Minimum wage earner</span>@endif</dd>
                    @foreach (GovernmentId::cases() as $id)
                        <dt class="col-sm-4">{{ $id->label() }}</dt><dd class="col-sm-8">{{ $employee->governmentId($id) ?: '—' }}</dd>
                    @endforeach
                    @isset($showUser)
                        <dt class="col-sm-4">User account</dt><dd class="col-sm-8">{{ $employee->user?->email ?? 'Not linked' }}</dd>
                    @endisset
                </dl>
            </div>
        </div>
    </div>
</div>
