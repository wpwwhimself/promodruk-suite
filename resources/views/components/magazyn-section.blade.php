@props([
    "title",
])

<section {{ $attributes }}>
    <div class="flex-right middle stretch">
        <h2>{{ $title }}</h2>

        <div class="flex-right buttons">
            @if (isset($buttons))
            {{ $buttons }}
            @endif
        </div>
    </div>

    {{ $slot }}
</section>
