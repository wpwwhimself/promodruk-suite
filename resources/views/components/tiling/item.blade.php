@props([
    "title",
    "icon" => null,
])

<li class="padded">
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
</li>
