@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<x-shipyard.app.section title="Filtry" icon="filter" :extended="false">
    <form method="GET" action="{{ route("products") }}" class="flex right center middle">
        <x-input-field type="text"
            name="search"
            label="Wyszukaj"
            :value="request()->get('search')"
            placeholder="SKU, nazwa, opis..."
        />
        <x-multi-input-field :options="$suppliers"
            name="supplier"
            label="Dostawca"
            :value="request()->get('supplier', 'all')"
            empty-option="Wszyscy"
        />

        <x-button action="submit" label="Filtruj" />
    </form>
</x-shipyard.app.section>

<x-magazyn-section title="Lista produktów" :icon="model_icon('products')">
    <x-slot:buttons>
        <x-shipyard.ui.button
            label="Produkty z mnożnikiem ceny (Ofertownik)"
            :action="route('products.ofertownik-price-multipliers.list')"
        />
        <a class="button" href="{{ route("product-discount-exclusions") }}">Produkty wykluczone z rabatowania (Kwazar)</a>
        <a class="button primary" href="{{ route("products-edit-family") }}">Dodaj produkt</a>
    </x-slot:buttons>

    <x-product.family-list :families="$families" />

    {{ $families->appends(["search" => request()->get("search")])->withQueryString()->links("components.shipyard.pagination.default") }}
</x-magazyn-section>

@endsection
