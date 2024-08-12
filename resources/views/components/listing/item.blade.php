@props([
    "title",
    "subtitle" => null,
    "icon" => null,
    "img" => null,
    "ghost" => false,
    "link" => null,
])

<li {{ $attributes->class(["ghost" => $ghost])->merge(["class" => "flex-right center spread padded"]) }}>
    <div class="flex-right middle">

        @if ($img) <img src="{{ $img }}" alt="{{ $title }}" class="thumbnail" /> @endif

        <div>
            @if ($link) <a href="{{ $link }}"> @endif
            <h3 class="flex-right middle">
                @if ($icon) {{ svg(("ik-".$icon)) }} @endif
                {{ $title }}
            </h3>
            @if ($link) </a> @endif
            @if ($subtitle) <h4 class="ghost">{{ $subtitle }}</h4> @endif
        </div>

    </div>

    {{ $slot }}

    @if (isset($buttons))
    <div class="actions flex-right center-both">
        {{ $buttons }}
    </div>
    @endif
</li>
