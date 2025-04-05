@props([
    "show" => false,
])

<div {{ $attributes->class(["fullscreen-popup", "flex-down", "center-both", "hidden" => !$show]) }}>
    <div class="contents rounded padded">
        {{ $slot }}
    </div>
</div>
