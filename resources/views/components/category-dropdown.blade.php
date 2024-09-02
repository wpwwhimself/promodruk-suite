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
        document.querySelectorAll(`#category-dropdown ul`)?.forEach(ul => {
            if (ul.dataset.level >= level) ul.remove()
        })
        document.querySelectorAll(`#category-dropdown ul[data-level="${level - 1}"] li.active`)?.forEach(li => {
            li.classList.remove("active")
        })

        target = document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`)
        target.classList.add("active")

        children = cat.children
    }
    else
    {
        target = document.querySelector(`#category-dropdown`)
        children = categories.filter(cat => cat.parent_id == null)
    }

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
}

openCategory(null, 1)

const hideCategory = (cat_id) => {
    document.querySelector(`#category-dropdown li[data-id="${cat_id}"] ul`)?.remove()
    document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`).classList.remove("active")
}
</script>
