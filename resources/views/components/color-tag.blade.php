@props([
    "color",
    "link" => null,
    "active" => false,
])

@if ($link) <a href="{{ $link }}"> @endif
<div {{ $attributes->class(["color-tag", "active" => $active]) }} style="--tile-color: {{ $color }}" {{ $attributes }}></div>
@if ($link) </a> @endif
