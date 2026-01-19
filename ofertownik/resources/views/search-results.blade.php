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

<script defer>
// 🧩 compatibility package with sidebar category browsing 🧩 //
const sidebarOuter = document.querySelector(`[role="sidebar-categories"]`);
const sidebarLoader = sidebarOuter.querySelector(`[role="loader"]`);
const sidebarContainer = sidebarOuter.querySelector(`[role="list"]`);

fetch(`/api/front/category/`)
    .then(res => res.json())
    .then(({data, tiles, sidebar}) => {
        // update sidebar
        sidebarLoader.classList.add("hidden");
        sidebarContainer.innerHTML = sidebar;
    })
    .catch(err => {
        console.log(err);
    });

function getCategory(category_id) {
    window.location.href = `/kategorie/id/${category_id}`;
}
// 🧩 compatibility package with sidebar category browsing 🧩 //
</script>

@endsection

@section("interactives")
{{-- $results->appends(compact("perPage", "sortBy"))->links() --}}
@endsection
