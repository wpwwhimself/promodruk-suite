@props([
    "categories" => [],
])

<x-shipyard.ui.input type="select-multiple"
    name="category[]"
    label="Kategorie dostawcy"
    icon="file-tree"
    :select-data="[
        'options' => $categories,
    ]"
/>
