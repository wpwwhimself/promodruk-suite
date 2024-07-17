@props([
    'type', 'name', 'label',
    'autofocus' => false,
    'required' => false,
    "disabled" => false,
    "value" => null,
    "small" => false
])

<div {{
    $attributes
        ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->merge([
            "class" => ($small) ? "input-container input-small" : "input-container",
            "for" => $name
        ])
    }}>

    @if($type != "hidden")
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
        onfocus="highlightInput(this)" onblur="clearHighlightInput(this)"
    >
        {{ html_entity_decode($value) }}
    </textarea>
    @else
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        @if ($type == "checkbox" && $value)
        checked
        @else
        {{ $attributes->merge(["value" => html_entity_decode($value)]) }}
        @endif
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        onfocus="highlightInput(this)" onblur="clearHighlightInput(this)"
    />
    @endif
</div>
