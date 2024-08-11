@extends("layouts.main")
@section("title", $product->name)
@section("subtitle", $product->id)

@section("before-title")
<x-breadcrumbs :category="$product->categories" :product="$product" />
@endsection

@section("content")

<div class="grid" style="grid-template-columns: repeat(2, 50%);">
    <div class="flex-down">
        <x-photo-gallery :images="$product->images" :thumbnails="$product->thumbnails" />
    </div>

    <div>
        @if ($product->family->count() > 1)
        <h3>Wybierz kolor, aby zobaczyć zdjęcia i sprawdzić stan magazynowy</h3>

        <div class="flex-right wrap">
            @foreach ($product->family as $alt)
            <x-color-tag :color="collect($alt->color)"
                :active="$alt->id == $product->id"
                :link="route('product', ['id' => $alt->id])"
            />
            @endforeach
        </div>
        @endif

        <x-stock-display :product-id="$product->id" :long="true" />

        <h3>Opis</h3>
        <div>{{ \Illuminate\Mail\Markdown::parse($product->description ?? "") }}</div>
        <div>{{ \Illuminate\Mail\Markdown::parse($product->extra_description ?? "") }}</div>

        <form action="{{ route('add-to-cart') }}" method="post">
            @csrf

            <h3>Parametry zapytania</h3>

            <input type="hidden" name="product_id" value="{{ $product->id }}">

            @foreach ($product->attributes as $attr)
            <x-multi-input-field
                :name="'attr-'.$attr['id']" :label="$attr['name']"
                :options="collect($attr['variants'])->flatMap(fn($var) => [$var['name'] => $var['id']])"
            />
            @endforeach

            <x-input-field type="TEXT" label="Planowane ilości do wyceny" placeholder="np. 100/200/300..." name="amount" rows="2" />
            <x-input-field type="TEXT" label="Komentarz do zapytania" placeholder="np. dotyczące projektu..." name="comment" />

            <div class="flex-right center">
                <x-button action="submit" label="Dodaj do koszyka" icon="cart" />
                @auth <x-button action="{{ route('products-edit', ['id' => $product->id]) }}" label="Edytuj produkt" icon="edit" /> @endauth
            </div>
        </form>
    </div>
</div>

@endsection
