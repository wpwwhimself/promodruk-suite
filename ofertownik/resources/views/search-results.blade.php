@extends("layouts.main")
@section("title", "Wyniki wyszukiwania dla: " . request("query"))
@section("subtitle", "Ilość na stronie: ".$results->count()." z ".$results->total())

@section("content")

{{ $results
    ->links("vendor.pagination.top", [
        "availableFilters" => [
            ["availability", "Dostępność", ["wszystkie" => null, "tylko dostępne" => "available"]],
            ["color", "Kolor", $colorsForFiltering, true],
            ["prefix", "Kod", $prefixesForFiltering, true],
        ],
        "extraFiltrables" => null, // disabled // $extraFiltrables,
        "availableSorts" => [
            'polecane' => 'default',
            'cena rosnąco' => 'price',
            'cena malejąco' => '-price',
        ],
    ])
}}

<x-tiling count="5" class="small-tiles large-gap">
    @forelse ($results as $product_family)
    <x-product-tile :product-family="$product_family" />
    @empty
    <p class="ghost">Brak dopasowań</p>
    @endforelse
</x-tiling>

{{ $results->links("vendor.pagination.bottom") }}

@endsection

@section("interactives")
{{-- $results->appends(compact("perPage", "sortBy"))->links() --}}
@endsection
