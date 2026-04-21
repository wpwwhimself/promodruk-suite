@props([
    "title",
    "smallTitle" => null,
    "subtitle" => null,
    "icon" => null,
    "img" => null,
    "ghost" => false,
    "link" => null,
    "showImgPlaceholder" => false,
    "imageCovering" => false,
])

@if ($link)
<a href="{{ $link }}" {{ $attributes->class(["no-underline", "flex-down", "animatable", "ghost" => $ghost]) }}>
@else
<li {{ $attributes->class(["flex-down", "ghost" => $ghost, "animatable"]) }}>
@endif

    <div class="upper-split">
        @if ($img || $showImgPlaceholder)
        <div @class(["thumbnail-wrapper", "covering" => $imageCovering])>
            @if ($img) <img src="{{ $img }}" alt="{{ $title }}" class="thumbnail" /> @endif
            @if ($showImgPlaceholder && !$img) <div class="no-photo ghost flex-down center middle">Brak zdjęcia</div> @endif
            @isset ($tag) {{ $tag }} @endisset
        </div>
        @endif

        <div class="content-wrapper padded">
            <div class="flex-right middle">
                @if ($icon) {{ svg(("ik-".$icon)) }} @endif

                <div>
                    <h3>
                        {{ $title }}
                        @if ($smallTitle) <small class="ghost">{{ $smallTitle }}</small> @endif
                    </h3>
                    @if ($subtitle) <h4 class="ghost">{{ $subtitle }}</h4> @endif
                </div>
            </div>

            {{ $slot }}
        </div>
    </div>

    <div class="lower-split">
        @if (isset($buttons))
        <div class="actions flex-right center middle wrap">
            {{ $buttons }}
        </div>
        @endif
    </div>

@if ($link)
</a>
@else
</li>
@endif
