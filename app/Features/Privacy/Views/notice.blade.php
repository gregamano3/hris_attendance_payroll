@extends('layouts.app')

@section('title', 'Privacy notice')

@section('page')
    @if (! $notice)
        <div class="callout callout-info">No privacy notice has been published yet.</div>
    @else
        <div class="card" style="max-width: 60rem">
            <div class="card-header"><h3 class="card-title">{{ $notice->title }} <span class="badge text-bg-light border">v{{ $notice->version }}</span></h3></div>
            <div class="card-body" style="white-space: pre-line">{{ $notice->body }}</div>
            <div class="card-footer">
                @if ($acknowledged)
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i> Acknowledged on {{ $acknowledged->acknowledged_at->format('M j, Y g:i A') }}</span>
                @else
                    <form method="post" action="{{ route('privacy.notice.acknowledge') }}">
                        @csrf
                        <button class="btn btn-primary" id="acknowledge-button">I acknowledge</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@stop
