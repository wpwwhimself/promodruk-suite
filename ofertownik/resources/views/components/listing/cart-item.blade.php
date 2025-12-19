@props([
    "product",
    "ghost" => false,
])

<li {{ $attributes->class(["ghost" => $ghost])->merge(["class" => "padded animatable cart-item"]) }}>
    <div {{ $attributes->class(["thumbnail-wrapper", "covering"]) }}>
        <img src="{{ $product->thumbnails->first() }}" class="thumbnail" {{ Popper::pop("<img src='".$product->thumbnails->first()."' class='thumbnail' />") }} />
    </div>

    <div class="flex-down">
        <a href="{{ route('product', ['id' => $product->front_id]) }}" class="no-underline" target="_blank">
            <h3>
                {{ $product->name }}
                <small class="ghost">
                    {{ $product->has_no_unique_images ? $product->family_prefixed_id : $product->front_id }}
                </small>
            </h3>
        </a>
        <div>
            {{ $slot }}
        </div>
    </div>

    @if (isset($buttons))
    <div class="actions flex-down">
        {{ $buttons }}
    </div>
    @endif
</li>
