@props([
    "title",
    "subtitle" => null,
    "icon" => null,
    "img" => null,
    "ghost" => false,
    "link" => null,
    "showImgPlaceholder" => false,
    "imageCovering" => false,
])

@if ($link)
<a href="{{ $link }}" {{ $attributes->class(["no-underline", "flex-right", "center", "spread", "animatable", "ghost" => $ghost]) }}>
@else
<li {{ $attributes->class(["ghost" => $ghost])->merge(["class" => "flex-right middle spread padded animatable"]) }}>
@endif

    <div class="flex-right middle">

        @if ($img || $showImgPlaceholder)
        <div {{ $attributes->class(["thumbnail-wrapper", "covering" => $imageCovering]) }}>
            @if ($img) <img src="{{ $img }}" alt="{{ $title }}" class="thumbnail" /> @endif
            @if ($showImgPlaceholder && !$img) <div class="no-photo ghost flex-down center middle">Brak zdjęcia</div> @endif
        </div>
        @endif

        <div>
            <h3 class="flex-right middle">
                @if ($icon) {{ svg(("ik-".$icon)) }} @endif
                {{ $title }}
            </h3>
            @if ($subtitle) <h4 class="ghost">{{ $subtitle }}</h4> @endif
            @if (isset($subButtons))
            <div class="flex-down">
                {{ $subButtons }}
            </div>
            @endif
        </div>
    </div>

    <div>
        {{ $slot }}
    </div>

    @if (isset($buttons))
    <div class="actions flex-down">
        {{ $buttons }}
    </div>
    @endif

@if ($link)
</a>
@else
</li>
@endif
