@extends("layouts.app")
@section("title", "Szczegóły oferty")

@section("content")

@foreach ($products as $product)
<x-app-section
    title="{{ $product['name'] }} ({{ $product['original_color_name'] }})"
    :subtitle="$product['id']"
    class="flex-down"
>

    <span>Wartość produktu netto:</span>
    <ul>
        @foreach ($quantities as $qty)
        <li>
            {{ $qty }} szt:
            <strong>{{ as_pln($product["price"] * $qty) }}</strong>
            <small class="ghost">{{ as_pln($product["price"]) }}/szt.</small>
        </li>
        @endforeach
    </ul>

    @foreach ($product["markings"] as $position_name => $techniques)
    <h3>{{ $position_name }}</h3>

    <div class="flex-down">
        @foreach ($techniques as $t)
        <x-offer-position :marking="$t" />
        @endforeach
    </div>
    @endforeach

</x-app-section>
@endforeach

@endsection
