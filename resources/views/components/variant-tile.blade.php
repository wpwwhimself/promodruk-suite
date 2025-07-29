@props([
    "color" => null,
    "variant" => null, //expecting $product->attribute_for_tile
    "link" => null,
])

@if (!isset($color->id)) @php
$color = App\Models\MainAttribute::invalidColor();
@endphp @endif

@if ($link)
<a href="{{ $link }}">
@endif

<div class="variant-tile" {{ $attributes }}
@if ($color->color == "multi")
    style="background: linear-gradient(in hsl longer hue to bottom right, red 0 0)"
@elseif (Str::substrCount($color->color, ";") > 0)
    style="
        --w-col-1: {{ Str::of($color->color)->matchAll('/(#[0-9a-f]{6})/')[0] ?? '' }};
        --w-col-2: {{ Str::of($color->color)->matchAll('/(#[0-9a-f]{6})/')[1] ?? '' }};
        --w-col-3: {{ Str::of($color->color)->matchAll('/(#[0-9a-f]{6})/')[2] ?? '' }};
        @switch (Str::substrCount($color->color, ";"))
            @case (2)
            background: conic-gradient(var(--w-col-1) 33%, var(--w-col-2) 33%, var(--w-col-2) 67%, var(--w-col-3) 67%);
            @break

            @case (1)
            background: linear-gradient(to bottom right, var(--w-col-1) 50%, var(--w-col-2) 50%);
            @break
        @endswitch
    "
@elseif ($color->color)
    style="--tile-color: {{ $color->color }}"
@elseif ($variant && $variant["selected"]["img"])
    style="
        @if (Str::startsWith($variant["selected"]["img"], "@txt@"))
            @php
            $text_contents = json_decode(Str::after($variant["selected"]["img"], "@txt@"), true);
            @endphp
        color: {{ $text_contents["color"] ?? "black" }};
        background-color: {{ $text_contents["bg_color"] ?? "white" }};

        @else
        background-image: url('{{ $variant["selected"]["img"] }}');

        @endif

        border-color: gray;
        @if ($variant["data"]["large_tiles"] ?? null) --dim: 7em; @endif
    "
@else
    style="
        --space: 15%;
        --w-col-1: gold;
        --w-col-2: #222;
        background: repeating-linear-gradient(to bottom right, var(--w-col-1), var(--w-col-1) var(--space), var(--w-col-2) var(--space), var(--w-col-2) calc(var(--space) * 2));
    "
@endif

    @php
    if ($variant) {
        $pop = $variant["selected"]["label"];
    } else {
        $pop = "$color->id. $color->name";
        if ($color->color != "multi" || $color->color != "") {
            foreach (explode(";", $color->color) as $col) {
                $colRgb = "RGB: " . implode(", ", [hexdec(substr($col, 1, 2)), hexdec(substr($col, 3, 2)), hexdec(substr($col, 5, 2))]);
                $pop .= "<br>$col / $colRgb";
            }
        }
    }
    @endphp
    {{ Popper::pop($pop) }}
>
    @isset ($text_contents)
    <span class="tile-text" style="
        font-family: '{{ $text_contents["font"] ?? "Arial" }}';
        font-size: {{ $text_contents["font_size"] ?? "12" }}pt;
        font-weight: {{ ($text_contents["bold"] ?? false) ? "bold" : "normal" }};
        font-style: {{ ($text_contents["italic"] ?? false) ? "italic" : "normal" }};
        text-decoration: {{ ($text_contents["underline"] ?? false) ? "underline" : "none" }};
    ">
        {{ $text_contents["text"] }}
    </span>
    @endisset
</div>
@if ($link)
</a>
@endif
