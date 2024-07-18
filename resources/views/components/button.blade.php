@props([
    "action",
    "label" => null,
    "icon" => null,
    "hideLabel" => false,
    "color" => null,
])

<a href="{{ $action }}"
    class="button-like flex-right center-both padded"
>
    @if ($icon) {{ svg(("ik-".$icon)) }} @endif
    @if (!$hideLabel) {{ $label }} @endif
</a>
