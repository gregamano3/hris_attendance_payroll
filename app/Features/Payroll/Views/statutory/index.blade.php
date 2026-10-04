@extends('layouts.app')

@section('title', 'Statutory rates')

@section('page')
    <div class="callout callout-warning">
        Rates are effective-dated: add a new version when SSS, PhilHealth, Pag-IBIG or BIR publish new rates.
        Each payroll run uses the versions in effect at the end of its period. Always verify against the official circulars.
    </div>

    <div class="row">
        @foreach ($schemes as $scheme)
            @php $versions = $rates->get($scheme->value, collect()); $current = $versions->first(); @endphp
            <div class="col-xl-4 col-lg-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ $scheme->label() }}</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Parameter</th>@foreach ($versions->take(2) as $version)<th class="text-end">{{ $version->effective_from->format('M j, Y') }}</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach ($scheme->parameters() as $key => $label)
                                    <tr><td class="small">{{ $label }}</td>@foreach ($versions->take(2) as $version)<td class="text-end">{{ $version->parameters[$key] ?? '—' }}</td>@endforeach</tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <form method="post" action="{{ route('payroll.statutory.store') }}" class="card-footer">
                        @csrf
                        <input type="hidden" name="scheme" value="{{ $scheme->value }}">
                        <details>
                            <summary class="mb-2">Add new version</summary>
                            <div class="row g-2">
                                <x-form.input name="effective_from" label="Effective from" type="date" col="col-12" required />
                                @foreach ($scheme->parameters() as $key => $label)
                                    <div class="col-6">
                                        <label class="form-label small mb-0" for="{{ $scheme->value }}-{{ $key }}">{{ $label }}</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm" id="{{ $scheme->value }}-{{ $key }}"
                                            name="parameters[{{ $key }}]" value="{{ $current?->parameters[$key] }}" required>
                                    </div>
                                @endforeach
                                <div class="col-12"><button class="btn btn-sm btn-primary">Save version</button></div>
                            </div>
                        </details>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        @foreach ($taxTables as $key => $brackets)
            @php [$frequency, $from] = explode('|', $key); @endphp
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Withholding tax — {{ str_replace('_', '-', $frequency) }} (from {{ \Illuminate\Support\Carbon::parse($from)->format('M j, Y') }})</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th class="text-end">Over</th><th class="text-end">Not over</th><th class="text-end">Base tax</th><th class="text-end">Rate on excess</th></tr></thead>
                            <tbody>
                                @foreach ($brackets as $bracket)
                                    <tr>
                                        <td class="text-end">{{ $bracket->lower_bound->format(false) }}</td>
                                        <td class="text-end">{{ $bracket->upper_bound?->format(false) ?? '—' }}</td>
                                        <td class="text-end">{{ $bracket->base_tax->format(false) }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $bracket->rate * 100, 2), '0'), '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="post" action="{{ route('payroll.statutory.tax.store') }}" class="card">
        @csrf
        <div class="card-header"><h3 class="card-title">Add withholding tax table</h3></div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <x-form.select name="frequency" label="Frequency" :options="['semi_monthly' => 'Semi-monthly', 'monthly' => 'Monthly']" col="col-md-3" required />
                <x-form.input name="effective_from" label="Effective from" type="date" col="col-md-3" required />
            </div>
            @error('brackets') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
            <table class="table table-sm">
                <thead><tr><th>Over (₱)</th><th>Not over (₱, blank = no limit)</th><th>Base tax (₱)</th><th>Rate on excess (0–1)</th></tr></thead>
                <tbody>
                    @for ($i = 0; $i < 7; $i++)
                        <tr>
                            @foreach (['lower', 'upper', 'base', 'rate'] as $field)
                                <td><input type="number" step="any" min="0" name="brackets[{{ $i }}][{{ $field }}]" class="form-control form-control-sm" aria-label="{{ $field }} {{ $i + 1 }}"></td>
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Save table</button></div>
    </form>
@stop
