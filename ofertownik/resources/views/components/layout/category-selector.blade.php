<select name="categories[]" multiple>
    @foreach ($allCategories as $cat)
    <option value="{{ $cat->id }}"
        {{ !$cat->visible ? 'disabled' : '' }}
        {{ $selectedCategories->contains($cat->id) ? 'selected' : '' }}
    >
        {{ $cat->breadcrumbs }}
    </option>
    @endforeach
</select>

<script>
const categoryDropdown = document.querySelector("[name='categories[]']")
const categorySearchDropdown = new Choices(categoryDropdown, {
    itemSelectText: null,
    noResultsText: "Brak wyników",
    shouldSort: false,
    removeItemButton: true,
});
</script>
