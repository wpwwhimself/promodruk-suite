@props([
    "variant",
    "link" => null,
    "active" => false,
    "pop" => null,
])

@if ($link) <a href="{{ $link }}"> @endif

<div {{ $attributes->class(["variant-tile", "active" => $active, "no-color" => $variant->get("color") == null]) }}
    @if ($variant->get("color") == "multi")
    style="background: linear-gradient(in hsl longer hue to bottom right, red 0 0)"
@elseif (Str::substrCount($variant->get('color'), ";") > 0)
    style="
        --w-col-1: {{ Str::of($variant->get('color'))->matchAll('/(#[0-9a-f]{6})/')[0] ?? '' }};
        --w-col-2: {{ Str::of($variant->get('color'))->matchAll('/(#[0-9a-f]{6})/')[1] ?? '' }};
        --w-col-3: {{ Str::of($variant->get('color'))->matchAll('/(#[0-9a-f]{6})/')[2] ?? '' }};
        @switch (Str::substrCount($variant->get('color'), ";"))
            @case (2)
            background: conic-gradient(var(--w-col-1) 33%, var(--w-col-2) 33%, var(--w-col-2) 67%, var(--w-col-3) 67%);
            @break

            @case (1)
            background: linear-gradient(to bottom right, var(--w-col-1) 50%, var(--w-col-2) 50%);
            @break
        @endswitch
    "
@elseif ($variant->get("color"))
    style="--tile-color: {{ $variant->get("color") }}"
@elseif ($variant->get("img"))
    style="
        background-image: url('{{ $variant->get("img") }}');
        border-color: gray;
        @if ($variant->get("large_tiles")) --dim: 6em; @endif
    "
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
