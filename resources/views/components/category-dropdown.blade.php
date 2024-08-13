<div id="category-dropdown">
    <div id="columns" class="flex-right">
        <ul data-level="1">
            @foreach ($categories->whereNull("parent_id") as $cat)
            <li class="animatable" data-id="{{ $cat->id }}"
                {{ $cat->children->count() > 0 ? 'onmouseover' : 'onclick' }}="openCategory({{ $cat->id }}, 2)"
            >
                {{ $cat->name }}
                @if ($cat->children->count() > 0) <x-ik-chevron-right class="show-more" /> @endif
            </li>
            @endforeach
        </ul>
    </div>
</div>

<script>
const categories = {!! json_encode($categories) !!}
const openCategory = (cat_id, level) => {
    cat = categories.find(cat => cat.id == cat_id)

    if (cat.children.length == 0) window.location.href = `/produkty/kategoria/${cat_id}`

    document.querySelectorAll(`#category-dropdown #columns ul`).forEach(ul => {
        if (ul.dataset.level >= level) ul.remove()
    })
    document.querySelectorAll(`#category-dropdown #columns ul[data-level="${level - 1}"] li.active`).forEach(li => {
        li.classList.remove("active")
    })

    document.querySelector(`#category-dropdown li[data-id="${cat_id}"]`).classList.add("active")

    document.querySelector(`#category-dropdown #columns ul:nth-child(${level - 1})`)
        .after(fromHTML(`<ul data-level="${level}">
            ${cat.children.map(ccat => `<li class="animatable" data-id="${ccat.id}"
                ${ccat.children.length > 0 ? 'onmouseover' : 'onclick'}="openCategory(${ccat.id}, ${level + 1})"
            >
                ${ccat.name}
                ${ccat.children.length > 0 ? `<x-ik-chevron-right class="show-more" />` : ''}
            </li>`).join("")}
        </ul>`))
}
</script>
