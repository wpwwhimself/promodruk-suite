@extends("layouts.admin")
@section("title", implode(" | ", [$tag?->name ?? "Nowy tag produktu", "Edycja tagu produktu"]))

@section("content")

<form action="{{ route("update-product-tags") }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $tag?->id }}" />

    <x-tiling count="auto" class="stretch-tiles">
        <x-tiling.item title="Parametry taga" icon="settings">
            <x-input-field type="text"
                name="name"
                label="Nazwa"
                :value="$tag?->name"
                required
            />

            <x-multi-input-field
                name="type"
                label="Typ"
                :value="$tag?->type"
                :options="array_flip(App\Models\ProductTag::TYPES)"
                required
            />

            <x-input-field type="color"
                name="ribbon_color"
                label="Kolor wstążki"
                :value="$tag?->ribbon_color"
                required
            />

            <x-input-field type="text"
                name="ribbon_text"
                label="Tekst na wstążce"
                :value="$tag?->ribbon_text"
                required
            />

            <x-input-field type="number"
                name="ribbon_text_size_pt"
                label="Rozmiar czcionki [pt]"
                :value="$tag?->ribbon_text_size_pt"
                min="1" max="15"
                required
            />

            <x-input-field type="color"
                name="ribbon_text_color"
                label="Kolor czcionki"
                :value="$tag?->ribbon_text_color"
                required
            />

            <x-input-field type="checkbox"
                name="gives_priority_on_listing"
                label="Produkty z tym tagiem są na szczycie listingu"
                :value="$tag?->gives_priority_on_listing"
            />
        </x-tiling.item>

        <x-tiling.item title="Podgląd kafelka produktu" icon="eye">
            <x-tiling count="auto" class="but-mobile-down small-tiles to-the-left middle">
                <x-product-tile :product="$product" :tag="$tag" />
            </x-tiling>
        </x-tiling.item>
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        @if ($tag) <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" /> @endif
    </div>
    <div class="flex-right center">
        <x-button :action="route('product-tags')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
