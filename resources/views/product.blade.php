@extends("layouts.product")
@section("title", $product->name)
@section("subtitle", $product->id)

@section("before-title")
<x-breadcrumbs :category="$product->categories" :product="$product" />
@endsection

@section("left-side")
<x-photo-gallery :images="$product->images" :thumbnails="$product->thumbnails" />
@endsection

@section("content")

<h2>
    <small class="ghost">Cena netto (bez znakowania):</small>
    {{ asPln($product->price) }}
</h2>

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

<form action="{{ route('add-to-cart') }}" method="post" enctype="multipart/form-data">
    @csrf

    <h3>Parametry zapytania</h3>

    <input type="hidden" name="product_id" value="{{ $product->id }}">

    @foreach ($product->attributes as $attr)
    <x-multi-input-field
        :name="'attr-'.$attr['id']" :label="$attr['name']"
        :options="collect($attr['variants'])->flatMap(fn($var) => [$var['name'] => $var['id']])"
    />
    @endforeach

    <x-input-field type="TEXT" label="Planowane ilości do wyceny" placeholder="100/200/300 lub żółty:100 szt., zielony:50 szt. itp." name="amount" rows="2" />
    <x-input-field type="TEXT" label="Komentarz do zapytania" placeholder="np. dotyczące projektu..." name="comment" />
    <x-input-field type="file" label="Dodaj pliki do zapytania" name="files[]" multiple onchange="listFiles()" hint="Maks. 5 plików o rozmiarze 20 GB; większe pliki prosimy przesłać w formie linku." />

    <div class="input-container" style="margin-top: 0">
        <label></label>
        <div class="file-list flex-down"></div>
    </div>

    <div class="actions flex-right center">
        <x-button action="submit" label="Dodaj do zapytania" icon="cart" />
        @auth <x-button action="{{ route('products-edit', ['id' => $product->id]) }}" label="Edytuj produkt" icon="edit" /> @endauth
    </div>
</form>

<style>
h1 {
    font-size: 1.75em;
}
.actions {
    margin-block: 2.5em;
}
</style>

<script>
const listFiles = () => {
    const fileList = document.querySelector(`.file-list`)
    const input = document.querySelector(`[name="files[]"]`)
    let errors = false

    if (!input.files) return

    if (input.files.length > 5 || Array.from(input.files).reduce((a, b) => a + b.size, 0) > 20 * 1024 * 1024 * 1024 /* 20 GB */) {
        window.alert("Dodano zbyt dużo lub zbyt duże pliki dla jednego produktu. Dodatkowe pliki można dodać w formie linku np. w komentarzu")
        errors |= true
    }

    fileList.innerHTML = ''
    Array.from(input.files ?? []).forEach(file => {
        fileList.innerHTML += `<x-button action="none" label="${file.name}, rozm. ${getFileSize(file.size)}" icon="file" />`
    })

    if (errors) {
        input.value = null
        return
    }

    fileList.innerHTML += `<span>
        <strong>Uwaga!</strong>
        Do momentu wysłania zapytania, dodany plik będzie przechowany na serwerze maks. 180 minut.
        W przypadku przekroczenia tego czasu (do wysłania zapytania) plik automatycznie zostanie usunięty.
        Po potwierdzeniu zapytania skutecznie dodane pliki będą przechowywane (pod linkiem w zapytaniu) przez 14 dni.
    </span>`
}
</script>

@endsection

@section("bottom-side")

<x-tabs :tabs="$product->tabs" />

<div>
    <h2>Podobne produkty</h2>
    <x-tiling count="auto">
        @foreach ($product->similar->random(fn ($prds) => min(5, count($prds))) as $product)
        <x-product-tile :product="$product" />
        @endforeach
    </x-tiling>
</div>

@endsection
