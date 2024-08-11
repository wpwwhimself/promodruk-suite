@extends("layouts.main")
@section("title", $category->name)
@section("subtitle", "Produkty")

@section("before-title")
<x-breadcrumbs :category="$category" />
@endsection

@section("content")

@if ($category->children->count())
<h2>Podkategorie</h2>
<x-tiling count="5">
    @foreach ($category->children as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="$cat->external_link ?? route('category-'.$cat->id)"
    >
    </x-tiling.item>
    @endforeach
</x-tiling>

@else

<x-tiling count="auto">
    @forelse ($products as $product)
    <x-tiling.item :title="$product->product_family_id"
        :subtitle="Str::limit($product->name, 40)"
        :img="collect($product->thumbnails)->first()"
        :link="route('product', ['id' => $product->family->first()->id])"
    >
        <span class="flex-right middle wrap">
            @if ($product->family->count() > 1)
            @foreach ($product->family as $i => $alt) @if ($i >= 28) <x-ik-ellypsis height="1em" /> @break @endif
            <x-color-tag :color="collect($alt->color)" class="small" />
            @endforeach
            @endif
        </span>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

@endif

@endsection

@section("interactives")

@if ($category->children->count() == 0)
{{ $products->links() }}
@endif

@endsection
