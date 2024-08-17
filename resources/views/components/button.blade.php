@props([
    "action" => null,
    "label" => null,
    "icon" => null,
    "hideLabel" => false,
    "iconRight" => false,
])

@if ($action == null)
<button disabled
@elseif ($action == "submit")
<button type="submit"
@elseif ($action == "none")
<button
@else
<a href="{{ $action }}"
@endif

    {{ $attributes->merge(["class" => "button-like animatable flex-right center-both padded"]) }}
>
    @if ($icon && !$iconRight) {{ svg(("ik-".$icon)) }} @endif
    @if (!$hideLabel) {{ $label }} @endif
    @if ($icon && $iconRight) {{ svg(("ik-".$icon)) }} @endif

    @if ($slot) {{ $slot }} @endif

@if (in_array($action, ["submit", "none"]) || $action == null)
</button>
@else
</a>
@endif
