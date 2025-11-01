@props([
    "product",
])

<div>
    <span class="flex down">
        <a href="{{ route("products-edit", $product->id) }}">{{ $product->name }}</a>
        <span>({{ $product->id }})</span>
        <span class="flex right middle">
            @if (count($product->sizes ?? []) > 1)
            <x-size-tag :size="collect($product->sizes)->first()" /> - <x-size-tag :size="collect($product->sizes)->last()" />
            @elseif (count($product->sizes ?? []) == 1)
            <x-size-tag :size="collect($product->sizes)->first()" />
            @endif
        </span>
    </span>

    @if (count($product->thumbnails))
    <div class="flex right middle wrap">
        @foreach ($product->thumbnails as $i => $thumbnail)
        @if ($i > 1) @break @endif
        <img class="thumbnail" src="{{ $thumbnail ? url($thumbnail) : null }}"
            {{-- Popper::pop("<img class='thumbnail' src='".url($thumbnail ? url($thumbnail) : null)."' />") --}}
        />
        @endforeach
    </div>
    @endif
</div>
