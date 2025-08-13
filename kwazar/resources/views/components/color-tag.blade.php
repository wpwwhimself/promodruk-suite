@props([
    "color",
    "link" => null,
])

@if ($link)
<a href="{{ $link }}">
@endif

<div class="color-tile" title="{{ $color->name }}" {{ $attributes }}
@if ($color->color == "multi")
    style="background: linear-gradient(in hsl longer hue to bottom right, red 0 0)"
@elseif (Str::contains($color->color, ";"))
    style="
        --w-col-1: {{ Str::before($color->color, ";") }};
        --w-col-2: {{ Str::after($color->color, ";") }};
        background: linear-gradient(to bottom right, var(--w-col-1), var(--w-col-1) 50%, var(--w-col-2) 50%);
    "
@elseif ($color->color)
    style="--tile-color: {{ $color->color }}"
@else
    style="
        --space: 15%;
        --w-col-1: gold;
        --w-col-2: #222;
        background: repeating-linear-gradient(to bottom right, var(--w-col-1), var(--w-col-1) var(--space), var(--w-col-2) var(--space), var(--w-col-2) calc(var(--space) * 2));
    "
@endif
>
</div>

@if ($link)
</a>
@endif
