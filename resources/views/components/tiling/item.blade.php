@props([
    "title",
    "subtitle" => null,
    "icon" => null,
    "img" => null,
    "ghost" => false,
    "link" => null,
])

@if ($link)
<a href="{{ $link }}">
@else
<li class="padded">
@endif

    <div class="flex-right middle">
        @if ($img) <img src="{{ $img }}" alt="{{ $title }}" class="thumbnail" /> @endif

        <div>
            <h3 class="flex-right middle">
                @if ($icon) {{ svg(("ik-".$icon)) }} @endif
                {{ $title }}
            </h3>
            @if ($subtitle) <h4 class="ghost">{{ $subtitle }}</h4> @endif
        </div>
    </div>

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
