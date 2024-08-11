@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

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

@endsection

@section("interactives")

{{ $products->appends(compact("perPage", "sortBy"))->links() }}

<div class="flex-right center">
    <x-button :action="route('products-import-init')" label="Importuj" icon="download" />
    <x-button :action="route('products-import-refresh')" label="Odśwież z Magazynu" icon="refresh" />
</div>

@endsection
