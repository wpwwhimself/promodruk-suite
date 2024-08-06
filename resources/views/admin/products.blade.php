@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<x-listing>
    @forelse ($products as $product)
    <x-listing.item
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
    </x-listing.item>
    @empty
    <p class="ghost">Brak synchronizowanych produktów</p>
    @endforelse
</x-listing>

@endsection

@section("interactives")

{{ $products->links() }}

<div class="flex-right center">
    <x-button :action="route('products-import-init')" label="Importuj" icon="download" />
    <x-button :action="route('products-import-refresh')" label="Odśwież z Magazynu" icon="refresh" />
</div>

@endsection
