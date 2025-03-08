@props([
    "color",
    "link" => null,
    "active" => false,
    "pop" => null,
])

@if ($link) <a href="{{ $link }}"> @endif

<div {{ $attributes->class(["color-tile", "active" => $active, "no-color" => $color->get("color") == null]) }}
    @if ($color->get("color") == "multi")
    style="background: linear-gradient(in hsl longer hue to bottom right, red 0 0)"
@elseif (Str::substrCount($color->get('color'), ";") > 0)
    style="
        --w-col-1: {{ Str::of($color->get('color'))->matchAll('/(#[0-9a-f]{6})/')[0] ?? '' }};
        --w-col-2: {{ Str::of($color->get('color'))->matchAll('/(#[0-9a-f]{6})/')[1] ?? '' }};
        --w-col-3: {{ Str::of($color->get('color'))->matchAll('/(#[0-9a-f]{6})/')[2] ?? '' }};
        @switch (Str::substrCount($color->get('color'), ";"))
            @case (2)
            background: conic-gradient(var(--w-col-1) 33%, var(--w-col-2) 33%, var(--w-col-2) 67%, var(--w-col-3) 67%);
            @break

            @case (1)
            background: linear-gradient(to bottom right, var(--w-col-1) 50%, var(--w-col-2) 50%);
            @break
        @endswitch
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
