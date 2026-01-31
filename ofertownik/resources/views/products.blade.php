@extends("layouts.main")
@section("title", $category->name)
@section("subtitle", isset($products) ? "Ilość na stronie: ".$products->count()." z ".$products->total() : null)

@section("before-title")

<x-breadcrumbs :category="$category" />

<x-carousel :imgs="$category->banners" />

@endsection

@section("content")

{!! $category->welcome_text !!}

@if ($category->children->count())
<x-tiling count="5" class="large-gap small-tiles">
    @foreach ($category->children->merge($category->related) as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="$cat->link"
        :target="$cat->external_link ? '_blank' : '_self'"
        show-img-placeholder
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
    ->withQueryString()
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
    @forelse ($products as $product_family)
    <x-product-tile :product-family="$product_family" />
    @empty
    <p class="ghost">Brak produktów w tej kategorii</p>
    @endforelse
</x-tiling>

{{ $products->withQueryString()->links("vendor.pagination.bottom") }}

<script defer>
// 🧩 compatibility package with sidebar category browsing 🧩 //
const sidebarOuter = document.querySelector(`[role="sidebar-categories"]`);
const sidebarLoader = sidebarOuter.querySelector(`[role="loader"]`);
const sidebarContainer = sidebarOuter.querySelector(`[role="list"]`);

fetch(`/api/front/category/{{ $category->id }}`)
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

@endif

@auth
<div class="flex-down">
    <x-button :action="route('products-ordering-manage', ['category' => $category])" label="Zarządzaj wyświetlanymi tutaj produktami (kolejność, kategoria)" icon="edit" target="_blank" />
    <x-button :action="route('products-category-assignment-manage', ['category' => $category])" label="Przenieś produkty z tej kategorii do innej" icon="anchor" target="_blank" />
    <x-button :action="route('admin.model.edit', ['model' => 'categories', 'id' => $category->id])" label="Edytuj kategorię" icon="edit" target="_blank" />
</div>
@endauth

@endsection

@if (!$category->children->count())
@section("heading-appends")

<form method="get" class="inline-search" onsubmit="toggleCategorySearchInProgress()">
    <search class="flex-right middle framed">
        <input id="category-query" type="text" placeholder="Wyszukaj w {{ $category->name }}" name="query" value="{{ request('query') }}"
            autocomplete="off"
        />
        <x-button action="submit" label="" icon="search" />
    </search>
    <span role="category-search-in-progress" class="hidden">
        <p style="text-align: center;">Szukamy produktów...</p>
        <x-loader />
    </span>
    <script>
    function toggleCategorySearchInProgress()
    {
        document.querySelector("[role='category-search-in-progress']").classList.toggle("hidden");
    }
    </script>
</form>

@endsection
@endif
