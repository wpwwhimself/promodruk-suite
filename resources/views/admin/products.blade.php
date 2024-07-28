@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<ul>
    @forelse ($products as $product)
    <li>
        @if (count($product->images)) <img class="inline" src="{{ url($product->images->first()) }}" /> @endif
        <a href="{{ route("products-edit", $product->id) }}">{{ $product->name }}</a>
        ({{ $product->id }})
    </li>
    @empty
    <li class="ghost">Brak utworzonych produktów</li>
    @endforelse
</ul>

<div class="flex-right">
    <a href="{{ route("products-edit") }}">Dodaj produkt</a>
    <a href="{{ route("products-import") }}">Importuj produkt dostawcy</a>
</div>

@endsection
