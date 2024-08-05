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
    </x-tiling.item>
    @endforeach
</x-tiling>

<h2>Produkty</h2>
@endif

{{ $products->links() }}

<x-tiling count="auto">
    @forelse ($products as $product)
    <x-tiling.item :title="$product->name"
        :subtitle="$product->product_family_id"
        :img="collect($product->thumbnails)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        <span class="flex-right wrap">
            @foreach ($product->family as $alt)
            <x-color-tag :color="collect($alt->color)" class="small" />
            @endforeach
        </span>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

{{ $products->links() }}

@endsection
