@props([
    "count" => 3
])

@if ($count == "auto")
<div class="tiling flex-right center">
@else
<div class="tiling grid" style="grid-template-columns: repeat({{ $count }}, {{ 100/$count }}%);">
@endif

    {{ $slot }}
</div>
