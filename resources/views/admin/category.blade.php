@extends("layouts.admin")
@section("title", implode(" | ", [$category->name ?? "Nowa kategoria", "Edycja kategorii"]))

@section("content")

<form action="{{ route('update-categories') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $category?->id }}" />

    <x-tiling>
        <x-tiling.item title="Copywriting" icon="horn">
            <x-input-field type="text" label="Nazwa" name="name" :value="$category?->name" />
            <x-input-field type="text" label="Etykieta" name="label" :value="$category?->label" />

            <label for="description">Opis</label>
            <x-easy-mde name="description" :options="[
                'initialValue' => $category?->description,
                'spellChecker' => false,
                'showIcons' => ['strikethrough'],
                'hideIcons' => ['image']
            ]" />
        </x-tiling.item>

        <x-tiling.item title="Linki" icon="exit">
            <x-input-field type="url" label="Miniatura" name="thumbnail_link" :value="$category?->thumbnail_link" />

            @if ($category?->thumbnail_link)
            <div class="flex-right center">
                <img src="{{ $category?->thumbnail_link }}" alt="Podgląd miniatury" class="thumbnail">
            </div>
            @endif

            <x-input-field type="url" label="Link zewnętrzny" name="external_link" :value="$category?->external_link" />
        </x-tiling.item>

        <x-tiling.item title="Powiązania" icon="link">
            <x-input-field type="checkbox" label="Widoczna" name="visible" :value="$category?->visible ?? true" />
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
    <div class="flex-right center">
        <x-button :action="route('categories')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
