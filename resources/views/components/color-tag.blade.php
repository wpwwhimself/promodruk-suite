@props([
    "color",
    "link" => null,
    "active" => false,
])

@if ($link) <a href="{{ $link }}"> @endif

<div {{ $attributes->class(["color-tag", "active" => $active, "no-color" => $color->color == null]) }}
    title="{{ $color->name }}"
    style="--tile-color: {{ $color->color }}"
>
</div>

@if ($link) </a> @endif
