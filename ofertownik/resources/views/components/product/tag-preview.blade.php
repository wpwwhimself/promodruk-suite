@props([
    "data",
])

@php
$product = \App\Models\Product::all()->random(1)->first();
$tag = $data;
@endphp

<p class="ghost">Zapisz zmiany, żeby odświeżyć podgląd.</p>

@if ($tag->getKey())
<x-tiling count="auto" class="but-mobile-down small-tiles to-the-left middle">
    <x-product-tile :product="$product" :tag="$tag" />
</x-tiling>
@endif
