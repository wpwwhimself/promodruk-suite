@extends("layouts.admin")
@section("title", "Produkty")

@section("content")

<ul>
    @forelse ($products as $product)
    <li>
        @if ($product->images) <img class="inline" src="{{ url($product->images->first()) }}" /> @endif
        <a href="{{ route("products-edit", $product->id) }}">{{ $product->name }}</a>
        ({{ $product->id }})
    </li>
    @empty
    <li class="ghost">Brak utworzonych produktów</li>
    @endforelse
</ul>

<a href="{{ route("products-edit") }}">Dodaj produkt</a>

@endsection
