@extends("layouts.main")
@section("title", $category->name)
@section("subtitle", "Produkty")

@section("content")

<x-breadcrumbs :category="$category" />

@if ($category->children->count())
<h2>Podkategorie</h2>
<x-tiling>
    @foreach ($category->children as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="$cat->external_link ?? route('category-'.$cat->id)"
    >
        <x-slot:buttons>
        </x-slot:buttons>
    </x-tiling.item>
    @endforeach
</x-tiling>

<h2>Produkty</h2>
@endif

<x-tiling>
    @forelse ($category->products as $product)
    <x-tiling.item :title="$product->name"
        :subtitle="$product->id"
        :img="collect($product->images)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        <x-slot:buttons>
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

@endsection
