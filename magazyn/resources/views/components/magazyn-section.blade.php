@props([
    "title",
    "subtitle" => null,
    "icon" => null,
])

<x-shipyard.app.section
    :title="$title"
    :subtitle="$subtitle"
    :icon="$icon"
    {{ $attributes }}
>
    @isset($buttons)
    <x-slot:actions>
        {{ $buttons }}
    </x-slot:actions>
    @endisset

    {{ $slot }}
</x-shipyard.app.section>
