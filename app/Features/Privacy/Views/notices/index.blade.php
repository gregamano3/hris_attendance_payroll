@extends('layouts.app')

@section('title', 'Privacy notices')

@section('page')
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
                <thead><tr><th>Version</th><th>Title</th><th>Status</th><th class="text-end">Acknowledged</th><th></th></tr></thead>
                <tbody>
                    @forelse ($notices as $notice)
                        <tr>
                            <td>{{ $notice->version }}</td>
                            <td>{{ $notice->title }}</td>
                            <td>{!! $notice->published_at ? '<span class="badge text-bg-success">Published '.$notice->published_at->format('M j, Y').'</span>' : '<span class="badge text-bg-secondary">Draft</span>' !!}</td>
                            <td class="text-end">{{ $notice->acknowledgements_count }} / {{ $activeUsers }}</td>
                            <td class="actions">
                                @unless ($notice->published_at)
                                    <form method="post" action="{{ route('privacy.notices.publish', $notice) }}" data-confirm="Publish this version? Every user will be asked to acknowledge it.">
                                        @csrf @method('patch')<button class="btn btn-sm btn-outline-primary">Publish</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-3">No notices yet. Users are not asked to acknowledge anything until one is published.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="{{ route('privacy.notices.store') }}" class="card">
        @csrf
        <div class="card-header"><h3 class="card-title">New version</h3></div>
        <div class="card-body row g-3">
            <x-form.input name="version" label="Version" col="col-md-2" placeholder="1.0" required />
            <x-form.input name="title" label="Title" col="col-md-10" value="Employee Privacy Notice" required />
            <x-form.textarea name="body" label="Notice (plain text)" rows="16" :value="$template" required />
            <div class="col-12 form-check form-switch ms-2">
                <input type="hidden" name="publish" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="publish" name="publish" value="1">
                <label class="form-check-label" for="publish">Publish now</label>
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Save</button></div>
    </form>
@stop
