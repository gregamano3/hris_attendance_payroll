{{-- Shared application layout: AdminLTE page + app assets + flash messages. --}}
@extends('adminlte::page')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0 fs-3">@yield('page_title', View::getSection('title'))</h1>
        <div>@yield('page_actions')</div>
    </div>
@stop

@section('content')
    @include('partials.flash')
    @yield('page')
@stop

@push('css')
    @vite('resources/css/app.css')
@endpush

@push('js')
    @vite('resources/js/app.js')
@endpush
