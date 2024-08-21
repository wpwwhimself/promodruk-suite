@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

{{ $products->appends(compact("perPage", "sortBy"))->links("vendor.pagination.tailwind", [
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
    <form action="{{ route('products') }}" method="get">
        <search class="flex-right middle" style="border: 1px solid hsl(var(--shade))">
            <input id="query" type="text" placeholder="Wyszukaj po SKU/tytule/opisie..." name="query" :value="request('query')" />
            <x-button action="submit" label="" icon="search" />
        </search>
    </form>

    <x-button :action="route('products-import-init')" label="Importuj" icon="download" />
    <x-button :action="route('products-import-refresh')" label="Odśwież z Magazynu" icon="refresh" />
</div>

@endsection
