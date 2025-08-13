@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<div class="flex-right center middle">
    <x-product-refresh-status />
</div>

<div class="flex-right center middle">
    <form action="{{ route('products') }}" method="get">
        <search class="flex-right middle" style="border: 1px solid hsl(var(--shade))">
            <input id="query" type="text" placeholder="Wyszukaj po SKU/tytule/opisie..." name="query" :value="request('query')" />
            <x-button action="submit" label="" icon="search" />
        </search>
    </form>

    <form action="{{ route('en-masse-init') }}" method="post" class="flex-right middle padded">
        @csrf
        <input type="hidden" name="model" value="ProductFamily">

        <strong>Wykonaj operację masową dla</strong>
        <x-button action="submit" name="ids" :value="collect(array_keys($products->items()))->join(';')" label="widocznych na tej stronie" />
        <x-button action="submit" label="wszystkich" />
    </form>
</div>

{{ $products->appends(compact("perPage", "sortBy"))->links("vendor.pagination.top", [
    "availableSorts" => [
        'nazwa rosnąco' => 'name',
        'nazwa malejąco' => '-name',
    ],
    "availableFilters" => [
        ["visibility", "Widoczność", VISIBILITIES],
        ["cat_id", "Kategoria", $catsForFiltering],
    ]
]) }}

<x-tiling count="auto">
    @forelse ($products as $product)
    <x-product-tile admin
        :product-family="$product"
        :ghost="$product->first()->visible < 2"
    />
    @empty
    <p class="ghost">Brak synchronizowanych produktów</p>
    @endforelse
</x-tiling>

{{ $products->appends(compact("perPage", "sortBy"))->links("vendor.pagination.bottom") }}

@endsection

@section("interactives")

<div class="flex-right center">
    <x-button :action="route('products-import-init')" label="Importuj" icon="download" />
</div>

@endsection
