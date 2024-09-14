@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<x-magazyn-section title="Lista produktów">
    <x-slot:buttons>
        <a class="button" href="{{ route("products-edit") }}">Dodaj produkt</a>
        <search>
            <form method="GET" action="{{ route("products") }}">
                <input type="text" name="search" placeholder="SKU, nazwa, opis..." value="{{ request()->get("search") }}" />
            </form>
        </search>
    </x-slot:buttons>


    <ul>
        @forelse ($products as $product)
        <li>
            <x-product-info :product="$product" />
        </li>
        @empty
        <li class="ghost">Brak utworzonych produktów</li>
        @endforelse
    </ul>

    {{ $products->appends(["search" => request()->get("search")])->links() }}
</x-magazyn-section>

@endsection
