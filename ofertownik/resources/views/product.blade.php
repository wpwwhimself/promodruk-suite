@extends("layouts.product")
@section("title", $product->name)
@section("subtitle", ($product->has_no_unique_images) ? $product->family_prefixed_id : $product->front_id)

@section("before-title")
<x-breadcrumbs :category="$product->categories->first()" :product="$product" />
@endsection

@section("left-side")
<x-photo-gallery :images="$product->image_urls" :thumbnails="$product->thumbnails" />

<x-tabs :tabs="$product->tabs" />
@endsection

@section("content")

<div style="margin-bottom: 1.5em">
    @if ($product->subtitle)
    <p style="margin: 0 0 0.5em;">{{ $product->subtitle }}</p>
    @endif

    @isset ($product->extra_filtrables["Marka"])
    <h2 style="margin: 0;">
        <small class="ghost">Marka:</small>
        {{ current($product->extra_filtrables["Marka"]) }}
    </h2>
    @endisset

    <h2 style="margin: 0;">
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

@if ($product->description || $product->extra_description)
<div role="product-description">
    <h3>{{ $product->description_label ?? "Opis" }}:</h3>
    <div>{{ \Illuminate\Mail\Markdown::parse($product->description ?? "") }}</div>
    <div>{{ \Illuminate\Mail\Markdown::parse($product->extra_description ?? "") }}</div>
</div>
@endif

@if ($product->specification)
<ul role="specification">
    @foreach ($product->specification as $key => $value)
    <li>
        <b>{{ $key }}:</b>
        @if (!is_array($value))
        {{ $value }}
        @else
        <ul>
            @foreach ($value as $vvalue)
            <li>{{ $vvalue }}</li>
            @endforeach
        </ul>
        @endif
    </li>
    @endforeach
</ul>
@endif

<form role="product-add-form" action="{{ route('add-to-cart') }}" method="post" enctype="multipart/form-data">
    @csrf

    <h3>Dodaj wytyczne do zapytania:</h3>

    <input type="hidden" name="product_id" value="{{ $product->front_id }}">

    <x-input-field type="TEXT"
        :label="$product->categories->first()->product_form_fields['amounts']['label']"
        :placeholder="$product->categories->first()->product_form_fields['amounts']['placeholder']"
        name="amount"
    />
    <x-input-field type="TEXT"
        :label="$product->categories->first()->product_form_fields['comment']['label']"
        :placeholder="$product->categories->first()->product_form_fields['comment']['placeholder']"
        name="comment"
    />
    <div>
        <strong>Dodawanie plików do zapytania (np. logo)</strong>
        <p>Plik/pliki do danego produktu będzie można dodać z poziomu koszyka zapytania.</p>
    </div>

    <div class="actions flex-right center">
        <x-button action="submit" label="Dodaj do zapytania" icon="cart" />
        @auth
        <x-button action="{{ route('products-edit', ['id' => $product->family_prefixed_id]) }}" label="Edytuj produkt" icon="edit" />
        @endauth
    </div>
</form>

<style>
h1 {
    font-size: 1.75em;
}
.actions {
    margin-block: 1.25em;
}
[role="product-add-form"] {
    & .input-container {
        display: block;
    }
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
    <x-tiling count="auto" class="but-mobile-down small-tiles to-the-left middle">
        @foreach ($product->related as $product)
        <x-product-tile :product="$product" />
        @endforeach
    </x-tiling>
</div>
@endif

@if (userCanSeeWithSetting("similar_products_visible"))
<div>
    <h2>Podobne produkty</h2>
    <x-tiling count="auto" class="but-mobile-down small-tiles to-the-left middle">
        @foreach ($product->similar->random(fn ($prds) => min(5, count($prds))) as $product)
        <x-product-tile :product="$product" />
        @endforeach
    </x-tiling>
</div>
@endif

@endsection
