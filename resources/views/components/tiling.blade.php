@props([
    "count" => 3
])

<div class="tiling grid" style="grid-template-columns: repeat({{ $count }}, 1fr);">
    {{ $slot }}
</div>
