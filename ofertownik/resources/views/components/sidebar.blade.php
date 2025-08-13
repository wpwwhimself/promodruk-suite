<aside>
    <h2>Kategorie produktów</h2>
    <progress style="width: 100%" />
</aside>

@php
$category = \App\Models\Category::find(Str::afterLast(Route::currentRouteName(), "-"))
    ?? \App\Models\Product::find(Route::current()?->id)?->categories->first();
@endphp

<script defer>
const openSidebarCategory = (cat_id, level) => {
    if (document.querySelector(`aside li[data-id="${cat_id}"] + ul`) !== null) {
        hideSidebarCategory(cat_id)
        return
    }

    const cat = categories.find(cat => cat.id == cat_id)
    // if (cat?.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    let target
    let children
    let fn

    if (level != 1)
    {
        document.querySelectorAll(`aside ul`)?.forEach(ul => {
            if (ul.dataset.level >= level) ul.remove()
        })
        document.querySelectorAll(`aside ul[data-level="${level - 1}"] li.active`)?.forEach(li => {
            li.classList.remove("active")
        })

        target = document.querySelector(`aside li[data-id="${cat_id}"]`)
        target.classList.add("active")

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
            @if ($category) ccat.id == {{ $category->id }} && "active", @endif
        ].filter(Boolean).join(' ')}"
            data-id="${ccat.id}"
            data-link="${ccat.link}"
            onclick="openSidebarCategory(${ccat.id}, ${level + 1})"
        >
            ${ccat.depth > 0 ? `<x-ik-chevron-right class="left" />` : ''}
            ${ccat.name}
            ${ccat.children.length > 0 ? `<x-ik-chevron-left class="right" />` : ''}
        </li>`).join("")}
    </ul>`))
}

const hideSidebarCategory = (cat_id) => {
    const clickedCat = document.querySelector(`aside li[data-id="${cat_id}"]`)
    clickedCat.nextSibling.remove()
    clickedCat.classList.remove("active")
}

// prime all categories to open itself upon clicking
const primeSidebarCategories = () => {
    document.querySelectorAll(`aside li`)?.forEach(li => {
        li.onclick = () => window.location.href = li.dataset.link ?? `/produkty/kategoria/${li.dataset.id}`
    })
}
</script>
