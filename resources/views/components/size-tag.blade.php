@props([
    "size" => null,
    "link" => null,
])

@if ($size)

@if ($link)
<a href="{{ $link }}">
@endif

<div class="size-tile" title="Rozmiar {{ $size }}" {{ $attributes }}>
    {{ $size }}
</div>

@if ($link)
</a>
@endif

@endif
