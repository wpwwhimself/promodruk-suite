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
        :subtitle="$product->id"
        :img="collect($product->images)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        Dostępne: <span id="stock-ind" data-product="{{ $product->id }}">...</span> szt.
    </x-tiling.item>
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

<script>
fetch("{{ env('MAGAZYN_API_URL') }}stock").then(res => res.json()).then(stocks => {
    stocks.forEach(stock => {
        const indicator = document.querySelector(`#stock-ind[data-product="${stock.id}"]`)
        if (!indicator) return
        indicator.textContent = stock.current_stock
    })
})
</script>

{{ $products->links() }}

@endsection
