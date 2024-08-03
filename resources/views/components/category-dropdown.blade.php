<div id="category-dropdown">
    <h2>Wybierz kategorię</h2>

    <div id="columns" class="flex-right">
        <ul class="flex-down" data-level="1">
            @foreach ($categories->whereNull("parent_id") as $cat)
            <li class="animatable" onclick="openCategory({{ $cat->id }}, 2)">{{ $cat->name }}</li>
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

    document.querySelector(`#category-dropdown #columns ul:nth-child(${level - 1})`)
        .after(fromHTML(`<ul class="flex-down" data-level="${level}">
            ${cat.children.map(ccat => `<li class="animatable" onclick="openCategory(${ccat.id}, ${level + 1})">${ccat.name}</li>`).join("")}
        </ul>`))
}
</script>
