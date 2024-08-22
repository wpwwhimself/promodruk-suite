@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<div class="flex-right center middle">
    <form action="{{ route('products') }}" method="get">
        <search class="flex-right middle" style="border: 1px solid hsl(var(--shade))">
            <input id="query" type="text" placeholder="Wyszukaj po SKU/tytule/opisie..." name="query" :value="request('query')" />
            <x-button action="submit" label="" icon="search" />
        </search>
    </form>

    <form action="{{ route('en-masse-init') }}" method="post" class="flex-right middle padded">
        @csrf
        <input type="hidden" name="model" value="Product">

        <strong>Wykonaj operację masową dla</strong>
        <x-button action="submit" name="ids" :value="collect($products->items())->pluck('id')->join(';')" label="widocznych na tej stronie" />
        <x-button action="submit" label="wszystkich" />
    </form>
</div>

{{ $products->appends(compact("perPage", "sortBy"))->links("vendor.pagination.top", [
    "availableSorts" => [
        'nazwa rosnąco' => 'name',
        'nazwa malejąco' => '-name',
    ],
    "availableFilters" => [
        ["visibility", "Widoczność", ["widoczne" => 1, "ukryte" => 0]],
        ["cat_id", "Kategoria", $catsForFiltering],
    ]
]) }}

<x-tiling count="auto">
    @forelse ($products as $product)
    <x-tiling.item
        :title="$product->name"
        :subtitle="$product->id"
        :img="collect($product->thumbnails)->first()"
        :ghost="!$product->visible"
    >

        <x-slot:buttons>
            <x-button
                :action="route('products-edit', ['id' => $product->id])"
                label="Edytuj"
                icon="tool"
            />
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak synchronizowanych produktów</p>
    @endforelse
</x-tiling>

{{ $products->appends(compact("perPage", "sortBy"))->links("vendor.pagination.bottom") }}

@endsection

@section("interactives")

<div class="flex-right center">
    <x-button :action="route('products-import-init')" label="Importuj" icon="download" />
    <x-button :action="route('products-import-refresh')" label="Odśwież z Magazynu" icon="refresh" />
</div>

@endsection
