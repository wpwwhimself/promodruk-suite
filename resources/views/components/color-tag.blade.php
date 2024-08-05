@props([
    "color",
    "link" => null,
    "active" => false,
])

@if ($link) <a href="{{ $link }}"> @endif

<div {{ $attributes->class(["color-tag", "active" => $active, "no-color" => $color->get("color") == null]) }}
    title="{{ $color->get("name") }}"
    style="--tile-color: {{ $color->get("color") }}"
>
</div>

@if ($link) </a> @endif
