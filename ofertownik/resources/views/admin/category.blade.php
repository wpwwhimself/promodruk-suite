@extends("layouts.admin")
@section("title", implode(" | ", [$category->name ?? "Nowa kategoria", "Edycja kategorii"]))

@section("content")

<form action="{{ route('update-categories') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $category?->id }}" />

    <x-tiling count="1" class="stretch-tiles">
        <x-tiling.item title="Copywriting" icon="horn">
            <x-input-field type="text" label="Nazwa" name="name" :value="$category?->name" />
            <x-input-field type="text" label="Etykieta" name="label" :value="$category?->label" />

            <x-ckeditor name="description" label="Opis" :value="$category?->description" />
            <x-ckeditor name="welcome_text" label="Tekst powitalny" :value="$category?->welcome_text" />
        </x-tiling.item>

        <x-tiling.item title="Linki" icon="exit">
            <x-input-field type="url" label="Miniatura" name="thumbnail_link" :value="$category?->thumbnail_link" />

            @if ($category?->thumbnail_link)
            <div class="flex-right center">
                <img src="{{ $category?->thumbnail_link }}" alt="Podgląd miniatury" class="thumbnail">
            </div>
            @endif

            <x-input-field type="JSON" :column-types="[
                'Kolejność' => 'number',
                'Link' => 'url',
            ]"
                label="Banery"
                name="banners"
                :value="$category?->banners"
            />
            <p class="ghost">
                Zalecane wymiary baneru to <strong>1016 × 200 px</strong>.
                Obrazki przekraczające te proporcje zostaną przeskalowane tak, aby zawierały się w całości karuzeli.
            </p>

            <x-input-field type="url" label="Link zewnętrzny" name="external_link" :value="$category?->external_link" />
        </x-tiling.item>

        <x-tiling.item title="Powiązania" icon="link">
            <x-multi-input-field label="Widoczna" name="visible" :value="$category?->visible ?? 2" :options="VISIBILITIES" />
            <x-input-field type="number" label="Priorytet" name="ordering" :value="$category?->ordering" />
            {{-- edytor subkategorii --}}
            <x-multi-input-field
                label="Kategoria nadrzędna"
                name="parent_id"
                :value="$category?->parent_id"
                :options="$parent_categories_available"
                empty-option="brak"
            />
        </x-tiling.item>
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        <x-button action="submit" name="mode" value="saveAndNew" label="...i stwórz nową" icon="save" />
        @if ($category)
        <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" />
        @endif
    </div>
    <p class="danger"><strong>Uwaga:</strong> usunięcie kategorii usunie wszystkie produkty, w których ta kategoria była jedyną dostępną.</p>
    <div class="flex-right center">
        <x-button :action="route('categories')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
