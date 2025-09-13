@props([
    "count" => 3
])

@if ($count == "auto")
<div {{ $attributes->class(["tiling", "notranslate", "flex-right", "but-mobile-down", "wrap", "center"]) }}>
@else
<div {{ $attributes->class(["tiling", "notranslate", "grid", "but-mobile-down"]) }} style="grid-template-columns: repeat({{ $count }}, 1fr);">
@endif

    {{ $slot }}
</div>
