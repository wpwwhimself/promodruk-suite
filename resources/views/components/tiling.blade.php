@props([
    "count" => 3
])

@if ($count == "auto")
<div {{ $attributes->class(["tiling", "notranslate", "flex-right", "wrap", "center"]) }}>
@else
<div {{ $attributes->class(["tiling", "notranslate", "grid"]) }} style="grid-template-columns: repeat({{ $count }}, var(--tile-width));">
@endif

    {{ $slot }}
</div>
