@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<x-magazyn-section title="Filtry">
    <form method="GET" action="{{ route("products") }}" class="flex-right center middle">
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
</x-magazyn-section>

<x-magazyn-section title="Lista produktów">
    <x-slot:buttons>
        <a class="button" href="{{ route("products-edit-family") }}">Dodaj produkt</a>
    </x-slot:buttons>

    <div class="grid" style="--col-count: 3">
        @forelse ($families as $family)
        <div>
            <x-product.family :family="$family" />
        </div>
        @empty
        <li class="ghost">Brak utworzonych produktów</li>
        @endforelse
    </div>

    {{ $families->appends(["search" => request()->get("search")])->withQueryString()->links() }}
</x-magazyn-section>

@endsection
