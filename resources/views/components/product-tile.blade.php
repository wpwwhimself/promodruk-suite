@props(["product"])

<x-tiling.item :title="Str::limit($product->name, 40)"
    :small-title="$product->product_family_id"
    :subtitle="asPln($product->price)"
    :img="collect($product->thumbnails)->first() ?? collect($product->images)->first()"
    show-img-placeholder
    :link="route('product', ['id' => $product->family->first()->id])"
>
    <span class="flex-right middle wrap">
        @if ($product->family->count() > 1)
        @foreach ($product->family as $i => $alt) @if ($i >= 28) <x-ik-ellypsis height="1em" /> @break @endif
        <x-color-tag :color="collect($alt->color)" class="small" />
        @endforeach
        @endif
    </span>
</x-tiling.item>
