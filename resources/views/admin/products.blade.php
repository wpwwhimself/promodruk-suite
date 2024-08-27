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
        @if (count($product->thumbnails))
        <img class="inline" src="{{ url($product->thumbnails->first(fn($img) => $img !== null)) }}"
            {{ Popper::pop("<img class='thumbnail' src='".url($product->thumbnails->first(fn($img) => $img !== null))."' />") }}
        />
        @endif
        <a href="{{ route("products-edit", $product->id) }}">{{ $product->name }}</a>
        ({{ $product->id }})
        <x-color-tag :color="$product->color" />
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
