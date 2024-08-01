@props([
    "color",
    "link" => null,
])

@if ($link)
<a href="{{ $link }}">
@endif

<div class="color-tile" style="--tile-color: {{ $color }}"></div>

@if ($link)
</a>
@endif
