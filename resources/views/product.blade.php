@extends("layouts.main")
@section("title", $product->name)
@section("subtitle", $product->id)

@section("content")

<x-breadcrumbs :category="$product->categories" />

<div class="grid" style="grid-template-columns: repeat(2, 1fr);">
    <div class="flex-down">
        <x-photo-gallery :images="$product->images" />

        <div>
            <span>
                Wariant: <strong>{{ $product->original_color_name }}</strong>
            </span>

            <div class="flex-right wrap">
                @foreach ($mainAttributeVariants as $alt)
                @php
                    $color = $mainAttributes->first(fn($attr) => Str::contains($attr['name'], $alt->original_color_name));
                    $color ??= [
                        'name' => $alt->original_color_name,
                        'color' => null,
                        'description' => '*brak podglądu*',
                    ];
                @endphp
                <x-color-tag :color="collect($color)"
                    :active="$alt->original_color_name == $product->original_color_name"
                    :link="route('product', ['id' => $alt->id])"
                />
                @endforeach
            </div>
        </div>
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
