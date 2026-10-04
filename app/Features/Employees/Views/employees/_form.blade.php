@php use App\Features\Employees\Enums\GovernmentId; @endphp
@csrf

<div class="card">
    <div class="card-header"><h3 class="card-title">Personal information</h3></div>
    <div class="card-body row g-3">
        <x-form.input name="first_name" label="First name" :value="$employee->first_name" col="col-md-4" required />
        <x-form.input name="middle_name" label="Middle name" :value="$employee->middle_name" col="col-md-3" />
        <x-form.input name="last_name" label="Last name" :value="$employee->last_name" col="col-md-3" required />
        <x-form.input name="suffix" label="Suffix" :value="$employee->suffix" col="col-md-2" placeholder="Jr., III" />
        <x-form.input name="birth_date" label="Birth date" type="date" :value="$employee->birth_date?->toDateString()" col="col-md-4" />
        <x-form.select name="gender" label="Gender" :options="$genders" :value="$employee->gender" col="col-md-4" placeholder="—" />
        <x-form.select name="civil_status" label="Civil status" :options="$civilStatuses" :value="$employee->civil_status" col="col-md-4" placeholder="—" />
        <x-form.input name="email" label="Email" type="email" :value="$employee->email" col="col-md-6" />
        <x-form.input name="mobile" label="Mobile" :value="$employee->mobile" col="col-md-6" placeholder="09XXXXXXXXX" />
        <x-form.textarea name="address" label="Address" :value="$employee->address" />
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Employment</h3></div>
    <div class="card-body row g-3">
        <x-form.input name="employee_no" label="Employee no." :value="$employee->employee_no" col="col-md-4" required />
        <x-form.select name="department_id" label="Department" :options="$departments" :value="$employee->department_id" col="col-md-4" placeholder="— None —" />
        <x-form.select name="position_id" label="Position" :options="$positions" :value="$employee->position_id" col="col-md-4" placeholder="— None —" />
        <x-form.select name="employment_type" label="Employment type" :options="$employmentTypes" :value="$employee->employment_type" col="col-md-4" required />
        <x-form.select name="status" label="Status" :options="$statuses" :value="$employee->status" col="col-md-4" required />
        <x-form.select name="user_id" label="Linked user account" :options="$users" :value="$employee->user_id" col="col-md-4" placeholder="— None —" />
        <x-form.select name="supervisor_id" label="Supervisor (approves requests)" :options="$supervisors" :value="$employee->supervisor_id" col="col-md-4" placeholder="— None —" />
        <x-form.input name="hired_at" label="Hire date" type="date" :value="$employee->hired_at?->toDateString()" col="col-md-4" required />
        <x-form.input name="regularized_at" label="Regularization date" type="date" :value="$employee->regularized_at?->toDateString()" col="col-md-4" />
        <x-form.input name="separated_at" label="Separation date" type="date" :value="$employee->separated_at?->toDateString()" col="col-md-4"
            help="Required when resigned or terminated." />
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Compensation &amp; government IDs</h3></div>
    <div class="card-body row g-3">
        <x-form.select name="rate_type" label="Rate type" :options="$rateTypes" :value="$employee->rate_type" col="col-md-4" required />
        <x-form.input name="basic_rate" label="Basic rate (₱)" type="number" step="0.01" min="0"
            :value="$employee->basic_rate?->toDecimal()" col="col-md-4" required />
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input type="hidden" name="is_minimum_wage_earner" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_minimum_wage_earner" name="is_minimum_wage_earner" value="1"
                    @checked(old('is_minimum_wage_earner', $employee->is_minimum_wage_earner))>
                <label class="form-check-label" for="is_minimum_wage_earner">Minimum wage earner (tax-exempt wages)</label>
            </div>
        </div>
        @foreach (GovernmentId::cases() as $id)
            <x-form.input :name="$id->value" :label="$id->label()" :value="$employee->governmentId($id)" col="col-md-3"
                :placeholder="$id->placeholder()" inputmode="numeric" />
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Bank account (payroll credit)</h3></div>
    <div class="card-body row g-3">
        <x-form.input name="bank_name" label="Bank" :value="$employee->bank_name" col="col-md-4" placeholder="e.g. BDO, BPI, Metrobank" />
        <x-form.input name="bank_account_name" label="Account name" :value="$employee->bank_account_name" col="col-md-4" />
        <x-form.input name="bank_account_no" label="Account number" :value="$employee->bank_account_no" col="col-md-4" inputmode="numeric" />
    </div>
</div>
