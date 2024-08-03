<input type="hidden" name="categories" value="{{ implode(",", $selectedCategories?->pluck("id")->all()) }}" />
<ul class="categories">
    @foreach ($selectedCategories as $cat)
    <li cat-id="{{ $cat->id }}">
        {{ $cat->breadcrumbs }}
        <small class="clickable" onclick="deleteCategory(this)">(×)</small>
    </li>
    @endforeach
</ul>

<div class="flex-down">
    <select onchange="addCategory(this)">
        <option value="" select></option>
        @foreach ($allCategories as $cat)
        <option value="{{ $cat->id }}" {{ !$cat->visible ? 'disabled' : '' }}>{{ $cat->breadcrumbs }}</option>
        @endforeach
    </select>
</div>

<script>
const addCategory = (slct) => {
    const new_category_id = slct.value

    // clear adder
    slct.value = "";

    if (document.querySelector("input[name=categories]").value.split(",").includes(new_category_id)) return

    // gather new variant data
    fetch(`/api/categories/${new_category_id}`)
        .then(res => res.json())
        .then(cat => {
            document.querySelector(".categories")
                .append(fromHTML(`<li cat-id="${cat.id}">
                    ${cat.breadcrumbs}
                    <small class="clickable" onclick="deleteCategory(this)">(×)</small>
                </li>`))

            let ids = document.querySelector("input[name=categories]").value.split(",")
            ids.push(cat.id)
            document.querySelector("input[name=categories]").value = ids.join(",")
        })
}
const deleteCategory = (btn) => {
    let ids = document.querySelector("input[name=categories]").value.split(",")
    ids = ids.filter(id => id != btn.closest("li").getAttribute("cat-id"))
    document.querySelector("input[name=categories]").value = ids.join(",")

    btn.closest("li").remove()
}
</script>
