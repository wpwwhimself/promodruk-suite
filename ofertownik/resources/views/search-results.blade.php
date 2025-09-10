@extends("layouts.main")
@section("title", $query)
@section("subtitle", "Wyniki wyszukiwania (".$results->count()." produktów)")

@section("content")

<x-tiling count="5" class="small-tiles large-gap">
    @forelse ($results as $product)
    <x-product-tile :product="$product" />
    @empty
    <p class="ghost">Brak dopasowań</p>
    @endforelse
</x-tiling>

@endsection

@section("interactives")
{{-- $results->appends(compact("perPage", "sortBy"))->links() --}}
@endsection
