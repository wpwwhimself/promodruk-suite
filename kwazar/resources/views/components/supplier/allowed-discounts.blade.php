@props([
    "data"
])

@foreach (\App\Models\Supplier::ALLOWED_DISCOUNTS as $label => $name)
<x-shipyard.ui.input type="checkbox"
    name="allowed_discounts[]"
    :label="$label"
    :value="$name"
    :checked="in_array($name, $data?->allowed_discounts ?? [])"
/>
@endforeach
