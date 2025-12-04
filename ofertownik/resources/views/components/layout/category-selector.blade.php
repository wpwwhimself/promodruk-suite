<x-shipyard.ui.input type="select" multiple
    name="categories[]"
    label="Kategorie"
    :icon="model_icon('categories')"
    :select-data="[
        'options' => $allCategories->map(fn ($cat) => [
            'label' => $cat->breadcrumbs,
            'value' => $cat->id,
        ]),
    ]"
    :value="$selectedCategories->pluck('id')->toArray()"
/>
