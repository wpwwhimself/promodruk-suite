@props([
    "title",
    "subtitle" => null,
])

<section {{ $attributes }}>
    <div class="flex-right middle stretch">
        <h2 class="title">
            {{ $title }}
            @if ($subtitle) <small class="ghost">{{ $subtitle }}</small> @endif
        </h2>

        <div class="flex-right buttons">
            @if (isset($buttons))
            {{ $buttons }}
            @endif
        </div>
    </div>

    {{ $slot }}
</section>
