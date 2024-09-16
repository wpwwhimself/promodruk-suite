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
        @foreach ($product->family as $i => $alt) @if ($i >= 28) <x-ik-ellypsis height="1em" /> @break @endif
        <x-color-tag :color="collect($alt->color)" class="small" />
        @endforeach
        @endif
    </span>
</x-tiling.item>
