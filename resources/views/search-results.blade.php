@extends("layouts.main")
@section("title", $query)
@section("subtitle", "Wyniki wyszukiwania")

@section("content")

<x-tiling count="auto">
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
