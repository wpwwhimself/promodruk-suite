@props([
    "size" => null,
    "link" => null,
    "active" => false,
    "pop" => null,
])

@if ($size)

@if ($link) <a href="{{ $link }}"> @endif

    <div {{ $attributes->class(["size-tile", "active" => $active]) }}
        @if ($pop)
        {{ Popper::pop($pop) }}
        @endif
    >
        {{ $size }}
    </div>

    @if ($link) </a> @endif

@endif
