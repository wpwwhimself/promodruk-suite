@props([
    "category" => null,
])

<x-shipyard.app.card title="Kategorie" :icon="model_icon('categories')" role="sidebar-categories">
    <x-shipyard.app.loader />
</x-shipyard.app.card>

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
        target = document.querySelector(`[role='sidebar-categories'] .loader`)
        children = categories.filter(cat => cat.parent_id == null)
    }

    if (children.length == 0) {
        {!! $onfinalclick !!}
    }

    document.querySelector("[role='sidebar-categories'] [role='loader']")?.remove();

    target.after(fromHTML(`<ul data-level="${level}">
        ${children.map(ccat => `<li class="${[
            "animatable",
            "interactive",
            ccat.depth == 0 && "bold",
            @if ($category->id) ccat.id == {{ $category->id }} && "active", @endif
        ].filter(Boolean).join(' ')}"
            data-id="${ccat.id}"
            data-link="${ccat.link}"
            {!! $onclick !!}
        >
            ${ccat.depth > 0 ? `<x-ik-chevron-right class="left" />` : ''}
            ${ccat.name}
            ${ccat.children.length > 0 ? `<x-ik-chevron-left class="right" />` : ''}
        </li>`).join("")}
    </ul>`))
}

{!! $onclickdef !!}

// init
const cat_loader = document.querySelector("[role='sidebar-categories'] .loader");

cat_loader.classList.remove("hidden");
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
    .finally(() => cat_loader.classList.add("hidden"));
</script>
