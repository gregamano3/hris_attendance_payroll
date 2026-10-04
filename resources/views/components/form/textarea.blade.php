@props(['name', 'label', 'id' => null, 'value' => null, 'col' => 'col-12', 'rows' => 2])

<div class="{{ $col }}">
    <label for="{{ $id ?? $name }}" class="form-label">{{ $label }}</label>
    <textarea id="{{ $id ?? $name }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
