@props([
    "action",
    "label" => null,
    "icon" => null,
    "hideLabel" => false,
])

@if ($action == "submit")
<button type="submit"
@else
<a href="{{ $action }}"
@endif

    {{ $attributes->merge(["class" => "button-like flex-right center-both padded"]) }}
>
    @if ($icon) {{ svg(("ik-".$icon)) }} @endif
    @if (!$hideLabel) {{ $label }} @endif

@if ($action == "submit")
</button>
@else
</a>
@endif
