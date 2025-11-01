@props([
    'type' => 'text',
    'name',
    'label' => null,
    "value" => null,
    "hints" => null,
    "columnTypes" => [],
])

<x-shipyard.ui.input
    :type="$type"
    :name="$name"
    :label="$label"
    :value="$value"
    :hint="$hints"
    :column-types="$columnTypes"
    {{ $attributes }}
/>
