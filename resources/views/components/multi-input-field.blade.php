@props([
    'type', 'name', 'label',
    'autofocus' => false,
    'required' => false,
    "disabled" => false,
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
    <label for="{{ $name }}">{{ $label }}</label>
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $autofocus ? "autofocus" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $required ? "required" : "" }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
        >
        @if ($emptyOption)
            <option value="" {{ $value ? "" : "selected" }}>– {{ $emptyOption ?? "brak" }} –</option>
        @endif
        @foreach ($options as $label => $val)
            <option value="{{ $val }}" {{ $value == $val ? "selected" : "" }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
