@props([
    'type' => "text", 'name', 'label',
    'autofocus' => false,
    'required' => false,
    "disabled" => false,
    "value" => null,
    "small" => false,
    "hint" => null,
])

<div {{
    $attributes
        ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->merge(["for" => $name])
        ->class(["input-small" => $small, "input-container"])
    }}>

    @if($type != "hidden")
    <label for="{{ $type == "radio" ? $name."-".$value : $name }}">{{ $label }}</label>
    @endif

    <div>
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
        @elseif ($type == "dummy")
        <pre>{{ $value ?? "—" }}</pre>
        @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $type == "radio" ? $name."-".$value : $name }}"
            @if ($type == "checkbox" && $value)
            checked
            @else
            {{ $attributes->merge(["value" => html_entity_decode($value)]) }}
            @endif
            {{ $autofocus ? "autofocus" : "" }}
            {{ $required ? "required" : "" }}
            {{ $disabled ? "disabled" : "" }}
            {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
            {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
        />
        @endif

        @if ($hint)
        <span class="ghost">{{ $hint }}</span>
        @endif
    </div>
</div>
