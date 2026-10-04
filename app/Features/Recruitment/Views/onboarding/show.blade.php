@extends('layouts.app')

@section('title', 'Onboarding — '.$employee->full_name)

@section('page')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <ul class="list-group list-group-flush" id="onboarding-tasks">
                    @forelse ($tasks as $task)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <form method="post" action="{{ route('recruitment.onboarding.toggle', $task) }}">
                                @csrf @method('patch')
                                <button class="btn btn-sm {{ $task->completed_at ? 'btn-success' : 'btn-outline-secondary' }}" aria-label="Toggle {{ $task->title }}">
                                    <i class="bi {{ $task->completed_at ? 'bi-check-lg' : 'bi-square' }}"></i>
                                </button>
                            </form>
                            <span @class(['text-decoration-line-through text-body-secondary' => $task->completed_at])>{{ $task->title }}</span>
                            <span class="ms-auto small text-body-secondary">
                                @if ($task->completed_at) Done {{ $task->completed_at->format('M j') }} by {{ $task->completer?->name }}
                                @elseif ($task->due_on) Due {{ $task->due_on->format('M j') }} @if ($task->due_on->isPast())<span class="badge text-bg-danger">Overdue</span>@endif
                                @endif
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">No tasks.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <form method="post" action="{{ route('recruitment.onboarding.start', $employee) }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">Add tasks from a template</h3></div>
                <div class="card-body"><x-form.select name="template_id" label="Template" :options="$templates" col="col-12" required /></div>
                <div class="card-footer"><button class="btn btn-outline-primary">Add</button></div>
            </form>
        </div>
    </div>
@stop
