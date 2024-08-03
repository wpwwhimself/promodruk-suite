@extends("layouts.main")
@section("title", $product->name)
@section("subtitle", $product->id)

@section("content")

<x-breadcrumbs :category="$product->categories" />

<div class="grid" style="grid-template-columns: repeat(2, 1fr);">
    <div class="flex-down">
        <x-photo-gallery :images="$product->images" />

        <span>
            Wariant: <strong>{{ $mainAttributes->firstWhere('id', $product->main_attribute_id)["name"] ?? "" }}</strong>
            @foreach ($mainAttributeVariants as $alt)
            <x-color-tag :color="$mainAttributes->firstWhere('id', $alt->main_attribute_id)['color']"
                :active="$alt->main_attribute_id == $product->main_attribute_id"
                :link="route('product', ['id' => $alt->id])"
            />
            @endforeach
        </span>
    </div>

    <div class="flex-down">
        <div>{{ \Illuminate\Mail\Markdown::parse($product->description ?? "") }}</div>
        <div>{{ \Illuminate\Mail\Markdown::parse($product->extra_description ?? "") }}</div>

        <form action="{{ route('add-to-cart') }}" method="post">
            @csrf

            <input type="hidden" name="product_id" value="{{ $product->id }}">

            @foreach ($product->attributes as $attr)
            <x-multi-input-field
                :name="'attr-'.$attr['id']" :label="$attr['name']"
                :options="collect($attr['variants'])->flatMap(fn($var) => [$var['name'] => $var['id']])"
            />
            @endforeach

            <x-input-field type="number" label="Liczba szt." name="amount" min="0" value="100" />
            <x-input-field type="TEXT" label="Komentarz" name="comment" />

            <x-stock-display :product-id="$product->id" :long="true" />

            <x-button action="submit" label="Dodaj do koszyka" icon="cart" />

        </form>

        @auth
        <x-button action="{{ route('products-edit', ['id' => $product->id]) }}" label="Edytuj produkt" icon="edit" />
        @endauth
    </div>
</div>

@endsection
