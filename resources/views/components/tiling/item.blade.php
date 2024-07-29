@props([
    "title",
    "icon" => null,
    "link" => null,
])

@if ($link)
<a href="{{ $link }}">
@else
<li class="padded">
@endif

    <h3 class="flex-right center-both">
        @if ($icon) {{ svg(("ik-".$icon)) }} @endif
        {{ $title }}
    </h3>
    {{ $slot }}

    @if (isset($buttons))
    <div class="actions flex-right center-both">
        {{ $buttons }}
    </div>
    @endif

@if ($link)
</a>
@else
</li>
@endif
