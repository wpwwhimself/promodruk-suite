@props([
    "title",
    "subtitle" => null,
    "icon" => null,
    "img" => null,
    "ghost" => false,
    "link" => null,
])

@if ($link)
<a href="{{ $link }}" class="no-underline animatable">
@else
<li>
@endif
    @if ($img)
    <div class="thumbnail-wrapper"><img src="{{ $img }}" alt="{{ $title }}" class="thumbnail" /></div>
    @endif

    <div class="content-wrapper padded">
        <h3 class="flex-right middle">
            @if ($icon) {{ svg(("ik-".$icon)) }} @endif
            {{ $title }}
        </h3>
        @if ($subtitle) <h4 class="ghost">{{ $subtitle }}</h4> @endif

        {{ $slot }}

        @if (isset($buttons))
        <div class="actions flex-right center-both">
            {{ $buttons }}
        </div>
        @endif
    </div>

@if ($link)
</a>
@else
</li>
@endif
