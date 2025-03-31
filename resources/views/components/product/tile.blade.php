@props([
    "product",
])

@if (count($product->thumbnails))
<img class="inline" src="{{ url($product->thumbnails->first(fn($img) => $img !== null)) }}"
    {{ Popper::pop("<img class='thumbnail' src='".url($product->thumbnails->first(fn($img) => $img !== null))."' />") }}
/>
@endif
<a href="{{ route("products-edit", $product->id) }}">{{ $product->name }}</a>
({{ $product->front_id }})
<x-color-tag :color="$product->color" />

@if (count($product->sizes ?? []) > 1)
<x-size-tag :size="collect($product->sizes)->first()" /> - <x-size-tag :size="collect($product->sizes)->last()" />
@elseif (count($product->sizes ?? []) == 1)
<x-size-tag :size="collect($product->sizes)->first()" />
@endif
