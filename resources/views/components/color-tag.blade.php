@props([
    "color",
    "link" => null,
    "active" => false,
    "pop" => null,
])

@if ($link) <a href="{{ $link }}"> @endif

<div {{ $attributes->class(["color-tag", "active" => $active, "no-color" => $color->get("color") == null]) }}
    @if ($color->get("color") == "multi")
    style="background: linear-gradient(in hsl longer hue to bottom right, red 0 0)"
@elseif (Str::contains($color->get("color"), ";"))
    style="
        --w-col-1: {{ Str::before($color->get("color"), ";") }};
        --w-col-2: {{ Str::after($color->get("color"), ";") }};
        background: linear-gradient(to bottom right, var(--w-col-1), var(--w-col-1) 50%, var(--w-col-2) 50%);
    "
@elseif ($color->get("color"))
    style="--tile-color: {{ $color->get("color") }}"
@else
    style="
        --space: 15%;
        --w-col-1: gold;
        --w-col-2: #222;
        background: repeating-linear-gradient(to bottom right, var(--w-col-1), var(--w-col-1) var(--space), var(--w-col-2) var(--space), var(--w-col-2) calc(var(--space) * 2));
    "
@endif

    @if ($pop)
    {{ Popper::pop($pop) }}
    @endif
>
</div>

@if ($link) </a> @endif
