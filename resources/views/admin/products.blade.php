@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<search>
    <form method="GET" action="{{ route("products") }}">
        <input type="text" name="search" placeholder="Wyszukaj..." value="{{ request()->get("search") }}" />
    </form>
</search>

<ul>
    @forelse ($products as $product)
    <li>
        <x-product-info :product="$product" />
    </li>
    @empty
    <li class="ghost">Brak utworzonych produktów</li>
    @endforelse
</ul>

<div class="flex-right">
    <a href="{{ route("products-edit") }}">Dodaj produkt</a>
</div>

{{ $products->appends(["search" => request()->get("search")])->links() }}

@endsection
