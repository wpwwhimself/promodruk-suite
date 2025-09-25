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

    <aside role="sidebar-categories">
        <h2>Kategorie</h2>
        <x-loader />
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
                :subtitle="$variant->family_prefixed_id"
                :img="$variant->thumbnails->first()"
                data-q="{{ $variant->front_id }} {{ $variant->name }}"
            >
                <x-input-field
                    type="number"
                    label="Priorytet"
                    name="ordering[{{ $family_id }}]"
                    :value="$variant->categoryData->ordering"
                />
                <x-button
                    icon="edit"
                    label="Edytuj"
                    :action="route('products-edit', ['id' => $family_id])"
                    target="_blank"
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

const openSidebarCategory = (breadcrumbs_cat_ids) => {
    let cat = undefined;
    breadcrumbs_cat_ids?.forEach(breadcrumb_id => {
        cat_id = breadcrumb_id;
        cat = (cat === undefined)
            ? categories.find(c => c.id == breadcrumb_id)
            : cat.children.find(c => c.id == breadcrumb_id);
    });

    let level = (breadcrumbs_cat_ids ?? []).length + 1;
    let target
    let children
    let fn

    if (level != 1)
    {
        target = document.querySelector(`[role='sidebar-categories'] li[data-id="${cat_id}"]`)
        children = cat.children
    }
    else
    {
        target = document.querySelector(`[role='sidebar-categories'] h2`)
        children = categories.filter(cat => cat.parent_id == null)
    }

    document.querySelector("[role='sidebar-categories'] [role='loader']")?.remove();

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
}

const goToCategory = (cat_id) => {
    window.location.href = `/admin/products/ordering/manage/${cat_id}`
}

fetch("/api/categories/for-front")
    .then(res => res.json())
    .then(data => {
        categories = data;

        // init categories
        openSidebarCategory(null);

        @if ($category)
        let breadcrumbs = [];
        {!! $category->tree->pluck("id")->toJson() !!}.forEach((cat_id, i, arr) => {
            // if (i == arr.length - 1) return // don't open last cat, it causes reloading loop if last cat is leaf
            breadcrumbs.push(cat_id);
            openSidebarCategory(breadcrumbs);
        })
        @endif
    })
</script>

@endsection
