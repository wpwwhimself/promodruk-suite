<aside class="flex-down">
    <div role="sidebar-categories">
        <h2>Kategorie produktów</h2>
        <x-loader />
    </div>

    <x-side-banner />
</aside>

@php
$category = \App\Models\Category::find(Str::afterLast(Route::currentRouteName(), "-"))
    ?? \App\Models\Product::find(Route::current()?->id)?->categories->first();
@endphp

<script defer>
const openSidebarCategory = (breadcrumbs_cat_ids) => {
    let cat = undefined;
    let cat_id = undefined;
    breadcrumbs_cat_ids?.forEach(breadcrumb_id => {
        cat_id = breadcrumb_id;
        cat = (cat === undefined)
            ? categories.find(c => c.id == breadcrumb_id)
            : cat.children.find(c => c.id == breadcrumb_id);
    });
    if (cat?.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    if (document.querySelector(`[role='sidebar-categories'] li[data-id="${cat_id}"] + ul`) !== null) {
        hideSidebarCategory(cat_id)
        return
    }

    // if (cat?.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    let level = (breadcrumbs_cat_ids ?? []).length + 1;
    let target
    let children
    let fn

    if (level != 1)
    {
        document.querySelectorAll(`[role='sidebar-categories'] ul`)?.forEach(ul => {
            if (ul.dataset.level >= level) ul.remove()
        })
        document.querySelectorAll(`[role='sidebar-categories'] ul[data-level="${level - 1}"] li.active`)?.forEach(li => {
            li.classList.remove("active")
        })

        target = document.querySelector(`[role='sidebar-categories'] li[data-id="${cat_id}"]`)
        target.classList.add("active")

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
            @if ($category) ccat.id == {{ $category->id }} && "active", @endif
        ].filter(Boolean).join(' ')}"
            data-id="${ccat.id}"
            data-link="${ccat.link}"
            onclick="openSidebarCategory([${[...(breadcrumbs_cat_ids ?? []), ccat.id].join(', ')}])"
        >
            ${ccat.depth > 0 ? `<x-ik-chevron-right class="left" />` : ''}
            ${ccat.name}
            ${ccat.children.length > 0 ? `<x-ik-chevron-left class="right" />` : ''}
        </li>`).join("")}
    </ul>`))
}

const hideSidebarCategory = (cat_id) => {
    const clickedCat = document.querySelector(`[role='sidebar-categories'] li[data-id="${cat_id}"]`)
    clickedCat.nextSibling.remove()
    clickedCat.classList.remove("active")
}

// prime all categories to open itself upon clicking
const primeSidebarCategories = () => {
    document.querySelectorAll(`[role='sidebar-categories'] li`)?.forEach(li => {
        li.onclick = () => window.location.href = li.dataset.link ?? `/produkty/kategoria/${li.dataset.id}`
    })
}
</script>
