@props([
    "product" => null,
    "productFamily" => null,
])

@php
$showcased = $product ?? $productFamily->random();
$product ??= $productFamily->sortBy("price")->first();
@endphp

<x-tiling.item :title="Str::limit($product->name, 40)"
    :small-title="$product->product_family_id"
    :subtitle="asPln($product->price)"
    :img="collect($showcased->thumbnails)->first() ?? collect($showcased->images)->first()"
    show-img-placeholder
    :link="route('product', ['id' => $showcased->family->first()->id])"
>
    <span class="flex-right middle wrap">
        @if ($product->family->count() > 1)

        @foreach (
            collect($product->family_variants_list["colors"])
                ->map(fn ($clr) => ["type" => "color", "var" => $clr])
                ->merge(
                    collect($product->family_variants_list["sizes"])
                        ->map(fn ($size) => ["type" => "size", "var" => $size])
                )
        as $i => $var)
            @if ($i >= 28) <x-ik-ellypsis height="1em" /> @break @endif

            @if ($var["type"] == "color")
                <x-color-tag :color="collect($var['var'])" class="small" />
            @else
                <x-size-tag :size="$var['var']" class="small" />
            @endif
        @endforeach

        @endif
    </span>
</x-tiling.item>
