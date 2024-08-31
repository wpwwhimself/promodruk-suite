@props([
    'type' => "text", 'name', 'label',
    "value" => null,
    "small" => false,
    "hint" => null,
    "clickToEdit" => false,
    "clickToSave" => false,
])

<div {{
    $attributes
        ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->merge(["for" => $name])
        ->class(["input-small" => $small, "input-container"])
    }}>

    @if($type != "hidden")
    <label for="{{ $type == "radio" ? $name."-".$value : $name }}"
        @if ($attributes->has("required")) {{ Popper::pop("Pole jest wymagane") }} @endif
    >
        {{ $label }}
        @if ($attributes->has("required")) <span class="danger">*</span> @endif
        @if ($clickToSave) <br><span class="clicker clickable" style="display: block; text-align: right;" onclick="submitNearestForm(this)">[zapisz]</span> @endif
    </label>
    @endif

    <div>
        @if ($type == "TEXT")
        <textarea
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $attributes->merge() }}
        >{{ html_entity_decode($value) }}</textarea>
        @elseif ($type == "dummy")
        <div class="flex-right">
            <pre>{{ $value ?? "—" }}</pre>
            @if ($clickToEdit) <span class="ghost" onclick="revealInput('{{ $name }}')">[edytuj]</span> @endif
        </div>
        {{-- <input type="hidden" name="{{ $name }}" value="{{ $value }}"> --}}
        @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $type == "radio" ? $name."-".$value : $name }}"
            {{ $attributes->merge([
                "checked" => $type == "checkbox" && $value,
                "value" => html_entity_decode($value),
            ]) }}
        />
        @endif

        @if ($hint)
        <span class="ghost">{{ $hint }}</span>
        @endif
    </div>
</div>
