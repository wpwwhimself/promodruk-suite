<div id="category-dropdown" onmouseleave="hideCategoryDropdown()">
    <ul data-level="1">
        @foreach ($categories->whereNull("parent_id") as $cat)
        <li class="animatable" data-id="{{ $cat->id }}"
            {{ $cat->children->count() > 0 ? 'onmouseenter' : 'onclick' }}="openCategory({{ $cat->id }}, 2)"
            onmouseleave="hideCategory({{ $cat->id }})"
        >
            {{ $cat->name }}
            @if ($cat->children->count() > 0) <x-ik-chevron-right class="show-more" /> @endif
        </li>
        @endforeach
    </ul>
</div>

<script>
const categories = {!! json_encode($categories) !!}
const openCategory = (cat_id, level) => {
    cat = categories.find(cat => cat.id == cat_id)

    if (cat.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    document.querySelectorAll(`#category-dropdown ul`).forEach(ul => {
        if (ul.dataset.level >= level) ul.remove()
    })
    document.querySelectorAll(`#category-dropdown ul[data-level="${level - 1}"] li.active`).forEach(li => {
        li.classList.remove("active")
    })

    const li = document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`)
    const li_pos = Array.prototype.indexOf.call(li.parentNode.children, li)
    li.classList.add("active")

    li.append(fromHTML(`<ul data-level="${level}">
            ${cat.children.map(ccat => `<li class="animatable" data-id="${ccat.id}"
                ${ccat.children.length > 0 ? 'onmouseenter' : 'onclick'}="openCategory(${ccat.id}, ${level + 1})"
                onmouseleave="hideCategory(${ccat.id})"
            >
                ${ccat.name}
                ${ccat.children.length > 0 ? `<x-ik-chevron-right class="show-more" />` : ''}
            </li>`).join("")}
        </ul>`))
}

const hideCategory = (cat_id) => {
    document.querySelector(`#category-dropdown li[data-id="${cat_id}"] ul`).remove()
}
</script>
