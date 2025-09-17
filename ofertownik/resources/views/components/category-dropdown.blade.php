<div id="category-dropdown" onmouseleave="toggleCategoryDropdown('remove')">
</div>

<script>
const goToCategory = (cat_id, url = null) => {
    window.location.href = url ?? `/produkty/kategoria/${cat_id}`
}

const openCategory = async (breadcrumbs_cat_ids) => {
    let cat = undefined;
    let cat_id = undefined;
    breadcrumbs_cat_ids?.forEach(breadcrumb_id => {
        cat_id = breadcrumb_id;
        cat = (cat === undefined)
            ? categories.find(c => c.id == breadcrumb_id)
            : cat.children.find(c => c.id == breadcrumb_id);
    });
    if (cat?.children.length == 0) goToCategory(null, cat.link)

    let level = (breadcrumbs_cat_ids ?? []).length + 1;
    let target
    let children = cat?.children ?? categories;

    if (level != 1)
    {
        target = document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`)

        if (target.classList.contains("active")) {
            hideCategory(cat_id)
            return
        }

        document.querySelectorAll(`#category-dropdown ul`)?.forEach(ul => {
            if (ul.dataset.level >= level) ul.remove()
        })
        document.querySelectorAll(`#category-dropdown ul[data-level="${level - 1}"] li.active`)?.forEach(li => {
            li.classList.remove("active")
        })
    }
    else
    {
        target = document.querySelector(`#category-dropdown`)
    }

    document.querySelector("[role='main-buttons'] [role='loader']")?.remove();

    target.classList.add("active")

    target.append(fromHTML(`<ul data-level="${level}">
        ${children.map(ccat => `<li class="animatable" data-id="${ccat.id}"
            onclick="event.stopPropagation(); goToCategory(null, '${ccat.link}')"
            ${ccat.children.length > 0 && `onmouseenter="openCategory([${[...(breadcrumbs_cat_ids ?? []), ccat.id].join(', ')}])"`}
            onmouseleave="hideCategory(${ccat.id})"
        >
            ${ccat.name}
            ${ccat.children.length > 0 ? `<x-ik-chevron-right class="show-more" />` : ''}
        </li>`).join("")}
    </ul>`))

    // hide other categories on mobile
    if (target != document.querySelector(`#category-dropdown`)) {
        Array.from(target.parentElement.children)
            .filter(li => !li.classList.contains("active"))
            .forEach(li => li.classList.add("but-mobile-hide"))
    }
}

const hideCategory = (cat_id) => {
    const target = document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`)
    target.classList.remove("active")
    Array.from(target.parentElement.children)
        .forEach(li => li.classList.remove("but-mobile-hide"))
}
</script>
