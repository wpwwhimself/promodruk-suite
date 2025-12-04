@props([
    "product" => null,
    "productFamily" => null,
    "admin" => false,
    "ghost" => false,
    "tag" => null,
])

@php
$showcased = $product ?? $productFamily->random();
$product ??= $productFamily->sortBy("price")->first();
$productFamily ??= $product->family;
$tag ??= $product->activeTag;
@endphp

<x-tiling.item :title="Str::limit($product->family_name, 40)"
    :small-title="($product->hide_family_sku_on_listing) ? null : $product->family_prefixed_id"
    :subtitle="$product->show_price ? asPln($product->price) : null"
    :img="$showcased->cover_image ?? collect($showcased->thumbnails)->first() ?? collect($showcased->image_urls)->first()"
    show-img-placeholder
    :link="$admin
        ? route('products-edit', ['id' => $product->family_prefixed_id])
        : route('product', ['id' => $product->front_id])"
    :ghost="$ghost"
>
    @if ($tag)
    <x-slot:tag>
        <x-product.tag :tag="$tag" />
    </x-slot:tag>
    @endif

    <x-product.variant-tiles-mini :product="$product" />
</x-tiling.item>
