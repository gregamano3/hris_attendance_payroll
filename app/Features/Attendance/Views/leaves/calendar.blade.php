@php use App\Features\Attendance\Enums\LeaveStatus; @endphp
@extends('layouts.app')

@section('title', 'Leave calendar — '.$month->format('F Y'))

@section('page_actions')
    <div class="btn-group">
        <a href="{{ route('leaves.calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary">‹ {{ $month->copy()->subMonth()->format('M') }}</a>
        <a href="{{ route('leaves.calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary">{{ $month->copy()->addMonth()->format('M') }} ›</a>
    </div>
@stop

@section('page')
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered mb-0" id="leave-calendar" style="table-layout: fixed; min-width: 56rem">
                <thead><tr>@foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)<th class="text-center">{{ $dayName }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($weeks as $week)
                        <tr style="height: 6.5rem">
                            @foreach ($week as $day)
                                <td @class(['bg-body-tertiary' => ! $day->isSameMonth($month), 'table-info' => $day->isToday()]) class="align-top p-1">
                                    <div class="small fw-semibold text-body-secondary">{{ $day->day }}</div>
                                    @foreach ($byDate[$day->toDateString()] ?? [] as $leave)
                                        <div class="badge text-bg-{{ $leave->status === LeaveStatus::Approved ? 'primary' : 'warning' }} d-block text-start text-truncate mb-1"
                                            title="{{ $leave->employee->full_name }} — {{ $leave->leaveType->name }} ({{ $leave->status->label() }})">
                                            {{ $leave->employee->first_name }} {{ mb_substr($leave->employee->last_name, 0, 1) }}. · {{ $leave->leaveType->code }}{{ $leave->day_part !== 'full' ? ' ½' : '' }}
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer small"><span class="badge text-bg-primary">Approved</span> <span class="badge text-bg-warning">Pending</span></div>
    </div>
@stop
