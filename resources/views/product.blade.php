@extends("layouts.product")
@section("title", $product->name)
@section("subtitle", $product->id)

@section("before-title")
<x-breadcrumbs :category="$product->categories->first()" :product="$product" />
@endsection

@section("left-side")
<x-photo-gallery :images="$product->images" :thumbnails="$product->thumbnails" />

<x-tabs :tabs="$product->tabs" />
@endsection

@section("content")

<div style="margin-bottom: 1.5em">
    <h2 style="margin-bottom: 0">
        <small class="ghost">
            @if ($product->price)
            Cena netto (bez znakowania):
            @else
            Cena:
            @endif
        </small>
        @if (!$product->price)
        <span style="color: hsl(var(--fg));">na zapytanie</span>
        @else
        {{ asPln($product->price) }}
        @endif
    </h2>
    <small>W celu wyceny, np. z logo, prosimy o dodanie produktu do zapytania (poniżej).</small>
</div>

<x-stock-display :product="$product" :long="true" />

<div role="product-description">
    <h3>Opis:</h3>
    <div>{{ \Illuminate\Mail\Markdown::parse($product->description ?? "") }}</div>
    <div>{{ \Illuminate\Mail\Markdown::parse($product->extra_description ?? "") }}</div>
</div>

<form role="product-add-form" action="{{ route('add-to-cart') }}" method="post" enctype="multipart/form-data">
    @csrf

    <h3>Dodaj wytyczne do zapytania:</h3>

    <input type="hidden" name="product_id" value="{{ $product->id }}">

    @foreach ($product->attributes as $attr)
    <x-multi-input-field
        :name="'attr-'.$attr['id']" :label="$attr['name']"
        :options="collect($attr['variants'])->flatMap(fn($var) => [$var['name'] => $var['id']])"
    />
    @endforeach

    <x-input-field type="TEXT"
        label="{{ $product->size_name ? 'Planowany kolor/rozmiar/ilość' : 'Planowane ilości' }} do wyceny"
        :placeholder="($product->size_name)
            ? 'np. czerwony S:15/L:10/XL:15, niebieski S:20/L:10/XXL:20 itp.'
            : 'np. 100/200/300 lub żółty:100 szt., zielony:50 szt.'
        "
        name="amount"
        rows="2"
    />
    <x-input-field type="TEXT" label="Komentarz do zapytania" placeholder="np. dotyczący znakowania lub specyfikacji zapytania" name="comment" />
    <div>
        <strong>Dodawanie plików do zapytania (np. logo)</strong>
        <p>Plik/pliki do danego produktu będzie można dodać z poziomu koszyka zapytania.</p>
    </div>

    <div class="actions flex-right center">
        <x-button action="submit" label="Dodaj do zapytania" icon="cart" />
        @auth <x-button action="{{ route('products-edit', ['id' => $product->product_family_id]) }}" label="Edytuj produkt" icon="edit" /> @endauth
    </div>
</form>

<style>
h1 {
    font-size: 1.75em;
}
.actions {
    margin-block: 1.25em;
}
</style>

<script>
// swap texts for mobile
if (window.innerWidth < 700) {
    document.querySelector(".tabs").before(
        document.querySelector(`[role="product-description"]`)
    )
    document.querySelector(".tabs").after(
        document.querySelector(`[role="product-add-form"]`)
    )
}
</script>

@endsection

@section("bottom-side")

@if (userCanSeeWithSetting("related_products_visible") && $product->related->count() > 0)
<div>
    <h2>Powiązane produkty</h2>
    <x-tiling count="5" class="but-mobile-down small-tiles to-the-left middle">
        @foreach ($product->related as $product)
        <x-product-tile :product="$product" />
        @endforeach
    </x-tiling>
</div>
@endif

@if (userCanSeeWithSetting("similar_products_visible"))
<div>
    <h2>Podobne produkty</h2>
    <x-tiling count="5" class="but-mobile-down small-tiles to-the-left middle">
        @foreach ($product->similar->random(fn ($prds) => min(5, count($prds))) as $product)
        <x-product-tile :product="$product" />
        @endforeach
    </x-tiling>
</div>
@endif

@endsection
