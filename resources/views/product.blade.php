@extends("layouts.main")
@section("title", implode(" | ", [$product->name, "Produkt"]))

@section("content")

<h2>{{ $product->name }}</h2>
<x-breadcrumbs :category="$product->categories" />

<div class="grid" style="grid-template-columns: repeat(2, 1fr);">
    <x-photo-gallery :images="$product->images" />

    <div class="flex-down">
        <div>{{ \Illuminate\Mail\Markdown::parse($product->description ?? "") }}</div>
        <div>{{ \Illuminate\Mail\Markdown::parse($product->extra_description ?? "") }}</div>

        @foreach ($product->attributes as $attr)
        <x-multi-input-field
            :name="'attr-'.$attr['id']" :label="$attr['name']"
            :options="collect($attr['variants'])->flatMap(fn($var) => [$var['name'] => $var['value']])"
        />
        @endforeach

        <x-input-field type="TEXT" label="Komentarz" name="comment" />

        <x-button action="" label="Dodaj do koszyka" icon="cart" />

        @auth
        <x-button action="{{ route('products-edit', ['id' => $product->id]) }}" label="Edytuj produkt" icon="edit" />
        @endauth
    </div>
</div>

@endsection
