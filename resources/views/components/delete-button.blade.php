@props(['action', 'label' => 'Delete', 'confirm' => 'Are you sure?'])

<form method="post" action="{{ $action }}" class="d-inline" data-confirm="{{ $confirm }}">
    @csrf
    @method('delete')
    <button type="submit" {{ $attributes->class(['btn btn-sm btn-outline-danger']) }}>{{ $label }}</button>
</form>
