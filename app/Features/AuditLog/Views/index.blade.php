@extends('layouts.app')

@section('title', 'Audit log')

@section('page')
    <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
        <div class="col-md-2">
            <label class="form-label small mb-1" for="type">Record</label>
            <select id="type" name="type" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1" for="id">ID</label>
            <input id="id" name="id" type="number" value="{{ $filters['id'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="user">User</label>
            <select id="user" name="user" class="form-select form-select-sm">
                <option value="">Anyone</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}" @selected($filters['user'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="event">Event</label>
            <select id="event" name="event" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach (['created', 'updated', 'deleted'] as $event)
                    <option value="{{ $event }}" @selected($filters['event'] === $event)>{{ ucfirst($event) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="from">From</label>
            <input id="from" type="date" name="from" value="{{ $filters['from']?->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1" for="to">To</label>
            <input id="to" type="date" name="to" value="{{ $filters['to']?->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-1 d-grid"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
    </form>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0 align-top">
                <thead><tr><th>When</th><th>User</th><th>Event</th><th>Record</th><th>Changes</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php $keys = array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? []))); @endphp
                        <tr>
                            <td class="text-nowrap small">{{ $log->created_at->format('M j, Y g:i:s A') }}</td>
                            <td class="small">{{ $log->user?->name ?? 'System' }}<div class="text-body-secondary">{{ $log->ip_address }}</div></td>
                            <td><span class="badge text-bg-{{ ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger'][$log->event] ?? 'secondary' }}">{{ $log->event }}</span></td>
                            <td class="small text-nowrap">
                                <a href="{{ route('audit-log', ['type' => $log->auditable_type, 'id' => $log->auditable_id]) }}">{{ $log->subject() }}</a>
                            </td>
                            <td class="small">
                                @foreach (array_slice($keys, 0, 12) as $key)
                                    <div>
                                        <code>{{ $key }}</code>:
                                        @if ($log->event === 'updated')
                                            <span class="text-danger text-decoration-line-through">{{ \Illuminate\Support\Str::limit(json_encode($log->old_values[$key] ?? null, JSON_UNESCAPED_UNICODE), 60) }}</span>
                                            →
                                        @endif
                                        <span>{{ \Illuminate\Support\Str::limit(json_encode(($log->event === 'deleted' ? $log->old_values : $log->new_values)[$key] ?? null, JSON_UNESCAPED_UNICODE), 60) }}</span>
                                    </div>
                                @endforeach
                                @if (count($keys) > 12)<div class="text-body-secondary">+{{ count($keys) - 12 }} more</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">No entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
@stop
