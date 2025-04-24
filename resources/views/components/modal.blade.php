@props([
    "show" => false,
])

<div {{ $attributes->class(["fullscreen-popup", "flex-down", "center-both", "hidden" => !$show]) }}>
    <div class="contents rounded padded">
        <x-button action="none" label="Zamknij" icon="close" onclick="toggleModal('{{ $attributes->get('id') }}')" />
        {{ $slot }}
    </div>
</div>
