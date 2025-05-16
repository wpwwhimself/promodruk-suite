@extends("layouts.main")
@section("title", $category->name)

@section("before-title")

<x-breadcrumbs :category="$category" />

<x-carousel :imgs="$category->banners" />

@endsection

@section("content")

{!! $category->welcome_text !!}

@if ($category->children->count())
<x-tiling count="5" class="large-gap small-tiles">
    @foreach ($category->children as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="$cat->link"
        :target="$cat->external_link ? '_blank' : '_self'"
        show-img-placeholder
        image-covering
    >
        {{ \Illuminate\Mail\Markdown::parse($cat->description ?? "") }}

        <x-slot:buttons>
            <x-button action="none" label="Szczegóły" icon="chevrons-right" class="small" />
        </x-slot:buttons>
    </x-tiling.item>
    @endforeach
</x-tiling>

@else

{{ $products
    ->appends(compact("perPage", "sortBy", "filters"))
    ->links("vendor.pagination.top", [
        "availableFilters" => [
            ["availability", "Dostępność", ["wszystkie" => null, "tylko dostępne" => "available"]],
            ["color", "Kolor", $colorsForFiltering, true],
        ],
        "extraFiltrables" => $extraFiltrables,
        "availableSorts" => [
            'cena rosnąco' => 'price',
            'cena malejąco' => '-price',
        ],
    ])
}}

<x-tiling count="5" class="small-tiles large-gap">
    @forelse ($products as $product_family)
    <x-product-tile :product-family="$product_family" />
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

{{ $products->links("vendor.pagination.bottom") }}

@endif

@endsection
