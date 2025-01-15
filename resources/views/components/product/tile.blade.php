@props([
    "product",
])

@if (count($product->thumbnails))
<img class="inline" src="{{ url($product->thumbnails->first(fn($img) => $img !== null)) }}"
    {{ Popper::pop("<img class='thumbnail' src='".url($product->thumbnails->first(fn($img) => $img !== null))."' />") }}
/>
@endif
<a href="{{ route("products-edit", $product->id) }}">{{ $product->name }}</a>
({{ $product->id }})
<x-color-tag :color="$product->color" />
<x-size-tag :size="$product->size_name" />
