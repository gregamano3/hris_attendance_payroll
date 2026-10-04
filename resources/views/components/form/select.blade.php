@props(['name', 'label', 'options' => [], 'value' => null, 'col' => 'col-md-6', 'placeholder' => null])

@php
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp

<div class="{{ $col }}">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            @if (is_array($optionLabel))
                <optgroup label="{{ $optionValue }}">
                    @foreach ($optionLabel as $groupValue => $groupLabel)
                        <option value="{{ $groupValue }}" @selected($selected === (string) $groupValue)>{{ $groupLabel }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endif
        @endforeach
    </select>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
