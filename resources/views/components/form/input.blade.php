@props(['name', 'label', 'id' => null, 'type' => 'text', 'value' => null, 'col' => 'col-md-6', 'help' => null])

<div class="{{ $col }}">
    <label for="{{ $id ?? $name }}" class="form-label">{{ $label }}</label>
    <input type="{{ $type }}" id="{{ $id ?? $name }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
