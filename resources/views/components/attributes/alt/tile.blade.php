@props([
    "attribute",
    "editable" => true,
])

<x-magazyn-section :title="$attribute->name">
    @if ($editable)
    <x-slot:buttons>
        <x-button :action="route('alt-attributes-edit', ['attribute' => $attribute])" label="Edytuj" />
    </x-slot:buttons>
    @endif

    @if ($attribute->description)
    <p>{{ $attribute->description }}</p>
    @endif

    <div class="flex-right wrap">
        <span>
            <strong>Opisane warianty</strong>:
            {{ count($attribute->variants) }}
        </span>
    </div>
</x-magazyn-section>
