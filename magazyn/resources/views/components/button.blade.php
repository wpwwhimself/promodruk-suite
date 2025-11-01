@props([
    "action" => null,
    "label" => null,
    "icon" => null,
    "hideLabel" => false,
])

<x-shipyard.ui.button
    :label="$label"
    :icon="$icon"
    :action="$action"
    {{ $attributes }}
/>
