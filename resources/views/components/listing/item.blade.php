@props([
    "title"
])

<li class="flex-right center spread padded">
    <h3>{{ $title }}</h3>

    {{ $slot }}

    <div class="actions flex-right center-both">
        {{ $buttons }}
    </div>
</li>
