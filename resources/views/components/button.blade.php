@props([
    "action" => null,
    "label" => null,
    "icon" => null,
    "iconSet" => "ik",
    "hideLabel" => false,
    "iconRight" => false,
    "pop" => null,
])

@if ($action == null)
<button disabled
@elseif ($action == "submit")
<button type="submit"
@elseif ($action == "none")
<span {{ $attributes->class("button-like animatable flex-right center-both padded") }}
@else
<a href="{{ $action }}"
@endif

    {{ $attributes->merge(["class" => "button-like animatable flex-right center-both padded"]) }}

    @if ($pop || $hideLabel)
    {{ Popper::pop($pop ?? $label) }}
    @endif
>
    @if ($icon && !$iconRight) {{ svg("$iconSet-$icon") }} @endif
    @if (!$hideLabel) <span>{{ $label }}</span> @endif
    @if ($icon && $iconRight) {{ svg("$iconSet-$icon") }} @endif

    @if ($slot) {{ $slot }} @endif

@if (in_array($action, ["submit"]) || $action == null)
</button>
@elseif ($action == "none")
</span>
@else
</a>
@endif
