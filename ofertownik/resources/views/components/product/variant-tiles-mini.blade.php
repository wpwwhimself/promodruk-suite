@props([
    "product",
])

@if ($product->family->count() > 0)
<span class="variant-tiles-container">
    @php
    $colors = $product->family_variants_list;
    @endphp

    @foreach (
        collect($colors)
            ->filter(fn ($clr) => ($clr["color"] ?? null) != null)
            ->filter(fn ($clr) => collect(explode("|", request("filters.color")))->reduce(
                fn ($total, $val_item) => empty(request("filters.color")) || $total || (
                    ($val_item == "pozostałe")
                        ? ($clr["color"] ?? null)
                        : Str::contains($clr["name"] ?? "", $val_item)
                ),
                false
            ))
            ->map(fn ($clr) => ["type" => "color", "var" => $clr])
    as $i => $var)
    @if ($i >= 28) <x-shipyard.app.icon name="dots-horizontal" /> @break @endif

    @if ($var["type"] == "color")
        <x-variant-tile :variant="collect($var['var'])" class="small" />
    @else
        <x-size-tag :size="$var['var']" class="small" />
    @endif
    @endforeach
</span>
@endif
