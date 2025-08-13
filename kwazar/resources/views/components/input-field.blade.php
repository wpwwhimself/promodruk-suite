@props([
    'type', 'name',
    'label' => null,
    'autofocus' => false,
    'required' => false,
    "disabled" => false,
    "value" => null,
    "small" => false
])

<div {{
    $attributes
        ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->filter(fn($val, $key) => (!Str::startsWith($key, 'on')))
        ->merge(["for" => $name])
        ->class(["input-small" => $small, "input-container", "inline" => $type == "checkbox"])
    }}>

    @if($type != "hidden" && $label)
    <label for="{{ $name }}">{{ $label }}</label>
    @endif

    @if ($type == "TEXT")
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
    >{{ html_entity_decode($value) }}</textarea>
    @else
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(["value" => html_entity_decode($value)]) }}
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
    />
    @endif
</div>
