@props([
    "size" => null,
    "link" => null,
])

@if ($size)

@if ($link)
<a href="{{ $link }}">
@endif

<div class="size-tile" title="Rozmiar {{ $size['size_name'] }}" {{ $attributes }}>
    {{ $size['size_name'] }}
</div>

@if ($link)
</a>
@endif

@endif
