@props([
    "title",
    "subtitle" => null,
    "icon" => null,
    "img" => null,
    "ghost" => false,
    "link" => null,
])

<li {{ $attributes->class(["ghost" => $ghost])->merge(["class" => "flex-right center spread padded"]) }}>
    @if ($link) <a href="{{ $link }}"> @endif
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
    @if ($link) </a> @endif

    {{ $slot }}

    <div class="actions flex-right center-both">
        {{ $buttons }}
    </div>
</li>
