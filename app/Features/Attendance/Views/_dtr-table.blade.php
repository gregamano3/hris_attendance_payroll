@php use App\Shared\Format; @endphp

<div class="row">
    @foreach ([
        ['Present', $totals['days_present'], 'success'],
        ['Absent', $totals['days_absent'], 'danger'],
        ['On leave', $totals['days_on_leave'], 'primary'],
        ['Late (h:mm)', Format::minutes($totals['late_minutes']), 'warning'],
        ['Undertime (h:mm)', Format::minutes($totals['undertime_minutes']), 'warning'],
        ['Overtime (h:mm)', Format::minutes($totals['regular_ot_minutes'] + $totals['rest_day_ot_minutes'] + $totals['special_holiday_ot_minutes'] + $totals['regular_holiday_ot_minutes']), 'info'],
    ] as [$label, $value, $theme])
        <div class="col-6 col-md-2">
            <div class="info-box mb-3">
                <span class="info-box-icon text-bg-{{ $theme }}"><i class="bi bi-calendar3"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ $label }}</span>
                    <span class="info-box-number">{{ $value }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-hover mb-0 align-middle" id="dtr-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>In</th>
                    <th>Out</th>
                    <th class="text-end">Worked</th>
                    <th class="text-end">Late</th>
                    <th class="text-end">UT</th>
                    <th class="text-end">OT</th>
                    <th class="text-end">ND</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($days as $day)
                    <tr @class(['table-secondary' => $day->is_rest_day])>
                        <td class="text-nowrap">{{ $day->date->format('D, M j') }}</td>
                        <td class="small text-body-secondary">{{ $day->shift?->name ?? '—' }}</td>
                        <td>{{ $day->time_in?->format('g:i A') ?? '—' }}</td>
                        <td>{{ $day->time_out?->format('g:i A') ?? '—' }}</td>
                        <td class="text-end">{{ Format::minutes($day->worked_minutes) }}</td>
                        <td class="text-end">{{ Format::minutes($day->late_minutes) }}</td>
                        <td class="text-end">{{ Format::minutes($day->undertime_minutes) }}</td>
                        <td class="text-end">{{ Format::minutes($day->overtime_minutes) }}</td>
                        <td class="text-end">{{ Format::minutes($day->night_diff_minutes) }}</td>
                        <td class="text-nowrap">
                            <span class="badge text-bg-{{ $day->status->badge() }}">{{ $day->status->label() }}</span>
                            @if ($day->holiday_type)
                                <span class="badge text-bg-{{ $day->holiday_type->badge() }}">{{ $day->holiday_type->label() }}</span>
                            @endif
                            @if ($day->leaveRequest)
                                <span class="badge text-bg-light border">{{ $day->leaveRequest->leaveType->code }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-body-secondary py-4">No records for this period.</td></tr>
                @endforelse
            </tbody>
            @if ($days->isNotEmpty())
                <tfoot class="fw-semibold">
                    <tr>
                        <td colspan="4">Totals</td>
                        <td class="text-end">{{ Format::minutes($totals['worked_minutes']) }}</td>
                        <td class="text-end">{{ Format::minutes($totals['late_minutes']) }}</td>
                        <td class="text-end">{{ Format::minutes($totals['undertime_minutes']) }}</td>
                        <td class="text-end">{{ Format::minutes($days->sum('overtime_minutes')) }}</td>
                        <td class="text-end">{{ Format::minutes($totals['night_diff_minutes']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
