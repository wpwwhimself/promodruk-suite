@props([
    "product",
    "ghost" => false,
])

<li {{ $attributes->class(["ghost" => $ghost])->merge(["class" => "padded animatable cart-item"]) }}>
    <div {{ $attributes->class(["thumbnail-wrapper", "covering"]) }}>
        <img src="{{ $product->thumbnails->first() }}" class="thumbnail" {{ Popper::pop("<img src='".$product->thumbnails->first()."' />") }} />
    </div>

    <div class="flex-right middle">
        <a href="{{ route('product', ['id' => $product->id]) }}" class="no-underline">
            <h3 class="flex-right middle">
                {{ $product->name }}
            </h3>
            <h4 class="ghost">{{ $product->id }}</h4>
        </a>
    </div>

    <div>
        {{ $slot }}
    </div>

    @if (isset($buttons))
    <div class="actions flex-down">
        {{ $buttons }}
    </div>
    @endif
</li>
