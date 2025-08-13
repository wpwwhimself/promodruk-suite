@props([
    "items",
    "value",
    "editable" => true,
])

<div id="categories-selector">

<x-multi-input-field
    label="Kategoria dostawcy" name="original_category"
    :options="$items"
    :value="$value" empty-option="wybierz"
    :required="$editable"
    :disabled="!$editable"
/>

</div>
