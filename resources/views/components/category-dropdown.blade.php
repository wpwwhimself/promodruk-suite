<div id="category-dropdown" onmouseleave="toggleCategoryDropdown('remove')">
</div>

<script>
const openCategory = (cat_id, level) => {
    const cat = categories.find(cat => cat.id == cat_id)
    if (cat?.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    let target
    let children

    if (level != 1)
    {
        target = document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`)
        children = cat.children

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
        children = categories.filter(cat => cat.parent_id == null)
    }

    target.classList.add("active")

    target.append(fromHTML(`<ul data-level="${level}">
        ${children.map(ccat => `<li class="animatable" data-id="${ccat.id}"
            onclick="event.stopPropagation(); openCategory(${ccat.id}, ${level + 1})"
            ${ccat.children.length > 0 && `onmouseenter="openCategory(${ccat.id}, ${level + 1})"`}
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

openCategory(null, 1)

const hideCategory = (cat_id) => {
    const target = document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`)
    target.nextSibling.querySelector(`ul`)?.remove()
    target.classList.remove("active")
    Array.from(target.parentElement.children)
        .forEach(li => li.classList.remove("but-mobile-hide"))
}
</script>
