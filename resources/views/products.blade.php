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
        :img="collect($product->images)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        <span class="flex-right" data-family-id="{{ $product->product_family_id }}">
        </span>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

<script>
fetch("{{ env('MAGAZYN_API_URL') }}products").then(res => res.json()).then(products => {
    const grouped = Object.groupBy(products, p => p.product_family_id)

    Object.keys(grouped).forEach(family => {
        const colors = grouped[family]
            .map(fam => fam.color)
            .map(clr => `<div class="color-tag ${clr.color == null ? 'no-color' : ''}" style="--tile-color: ${clr.color ?? "none"}"></div>`)

        const colorBar = document.querySelector(`span[data-family-id="${family}"]`)
        if (!colorBar) return

        colors.forEach(tag => colorBar.append(fromHTML(tag)))
    })
})
</script>

{{ $products->links() }}

@endsection
