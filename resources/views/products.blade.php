@extends("layouts.main")
@section("title", "Produkty")

@section("content")

<h2>{{ $category->name }}</h2>
<x-breadcrumbs :category="$category" />

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

<x-tiling>
    @forelse ($category->products as $product)
    <x-tiling.item :title="$product->name"
        :subtitle="$product->id"
        :img="collect($product->images)->first()"
    >
        <x-slot:buttons>
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

@endsection
