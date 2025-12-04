@props([
    "product",
])

<img
    src="{{ url($product->thumbnails->first(fn($img) => $img !== null)) }}"
    alt="{{ $product->name }}"
    class="product-thumbnail"
    {{ Popper::pop("<img class='thumbnail' src='".url($product->thumbnails->first(fn($img) => $img !== null))."' />") }}
>
