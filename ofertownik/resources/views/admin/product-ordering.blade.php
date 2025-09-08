@extends("layouts.admin")
@section("title", "Zarządzanie kolejnością produktów")

@section("content")

<p>
    Ten panel pozwala na nadanie własnego priorytetu dla produktów wyświetlanych na listingu kategorii.
</p>

<form class="grid" style="grid-template-columns: 1fr 4fr;"
    action="{{ route('products-ordering-submit') }}"
    method="post"
>
    @csrf

    <aside role="category-selector">
        <h2>Kategorie</h2>
        <progress style="width: 100%" />
    </aside>

    <main>
        @if (!$category->id)
        <p>Wybierz kategorię, aby wyświetlić produkty do niej przypisane.</p>
        @else
        <div class="flex-right middle spread">
            <h1>{{ $category->name }}</h1>

            <x-input-field type="text" name="filter" label="Filtruj (nazwa, SKU)" oninput="filterProducts(event.target.value)" hint="Użyj ; do dodawania kolejnych wyszukiwań do tej samej listy" />
            <script>
            function filterProducts(query) {
                document.querySelectorAll("[role='products'] li").forEach(row => {
                    const row_q = row.dataset.q.toLowerCase();
                    const show = (query.length > 0)
                        ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string))
                        : true;
                    row.classList.toggle("hidden", !show);
                });
            }
            </script>

            <x-button action="submit" label="Zapisz dla tej kategorii" icon="save" />
        </div>

        <input type="hidden" name="category_id" value="{{ $category->id }}">


        <x-listing role="products">
            @forelse ($category->products->groupBy("product_family_id") as $family_id => $variants)
            @php $variant = $variants->first(); @endphp
            <x-listing.item
                :title="$variant->family_name"
                :subtitle="$family_id"
                :img="$variant->thumbnails->first()"
                data-q="{{ $variant->sku }} {{ $variant->name }}"
            >
                <x-input-field
                    type="number"
                    label="Priorytet"
                    name="ordering[{{ $family_id }}]"
                    :value="$variant->categoryData->ordering"
                />
            </x-listing.item>
            @empty
            <p class="ghost">Brak produktów w tej kategorii</p>
            @endforelse
        </x-listing>

        <div class="flex-right center">
            <x-button action="submit" label="Zapisz dla tej kategorii" icon="save" />
        </div>
        @endif
    </main>
</div>

<script>
// list categories
let categories;

const openSidebarCategory = (cat_id, level) => {
    const cat = categories.find(cat => cat.id == cat_id)
    // if (cat?.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    let target
    let children
    let fn

    if (level != 1)
    {
        target = document.querySelector(`aside li[data-id="${cat_id}"]`)
        children = cat.children
    }
    else
    {
        target = document.querySelector(`aside h2`)
        children = categories.filter(cat => cat.parent_id == null)
    }

    document.querySelector("aside progress")?.remove();

    target.after(fromHTML(`<ul data-level="${level}">
        ${children.map(ccat => `<li class="${[
            "animatable",
            ccat.depth == 0 && "bold",
            @if ($category->id) ccat.id == {{ $category->id }} && "active", @endif
        ].filter(Boolean).join(' ')}"
            data-id="${ccat.id}"
            data-link="${ccat.link}"
            onclick="goToCategory(${ccat.id})"
        >
            ${ccat.depth > 0 ? `<x-ik-chevron-right class="left" />` : ''}
            ${ccat.name}
            ${ccat.children.length > 0 ? `<x-ik-chevron-left class="right" />` : ''}
        </li>`).join("")}
    </ul>`))

    children.forEach(ccat => {
        if (ccat.children.length > 0) {
            openSidebarCategory(ccat.id, level + 1)
        }
    })
}

const goToCategory = (cat_id) => {
    window.location.href = `/admin/products/ordering/manage/${cat_id}`
}

fetch("/api/categories/for-front")
    .then(res => res.json())
    .then(data => {
        categories = data;

        // init categories
        openSidebarCategory(null, 1);
    })
</script>

@endsection
