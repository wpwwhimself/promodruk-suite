@props([
    'type', 'name', 'label',
    'options',
    'emptyOption' => false,
    'value' => null,
    'small' => false
])

<div {{
    $attributes
        ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->class(["input-small" => $small, "input-container"])
    }}>
    <label for="{{ $name }}"
        @if ($attributes->has("required")) {{ Popper::pop("Pole jest wymagane") }} @endif
    >
        {{ $label }}
        @if ($attributes->has("required")) <span class="danger">*</span> @endif
    </label>
    <div>
        <select
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $attributes->merge() }}
            >
            @if ($emptyOption)
                <option value="" {{ $value ? "" : "selected" }}>{{ $emptyOption ?? "brak" }}</option>
            @endif
            @foreach ($options as $label => $val)
                <option value="{{ $val }}" {{ $value != null && ($value == $val || $attributes->has("multiple") && in_array($val, explode(",", $value))) ? "selected" : "" }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
