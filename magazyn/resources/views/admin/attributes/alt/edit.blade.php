@extends("layouts.admin")
@section("title", implode(" | ", [$attribute?->name ?? "Nowa cecha", "Edycja cechy"]))

@section("content")

<form action="{{ route('alt-attributes-process') }}" method="post"
    class="flex down"
>
    @csrf
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <div class="grid" style="--col-count: 2">
        <x-magazyn-section title="Podstawowe informacje">
            <x-input-field type="text"
                label="Warianty reprezentują:"
                name="name"
                placeholder="np. Kolory, Formaty, ..."
                :value="$attribute?->name"
                required
            />
            <x-input-field type="TEXT"
                label="Opis"
                name="description"
                :value="$attribute?->description"
            />
        </x-magazyn-section>

        <x-magazyn-section title="Oznaczenia wariantów">
            <x-shipyard.ui.input type="checkbox"
                label="Duże kafelki"
                name="large_tiles"
                :checked="$attribute?->large_tiles"
            />
            <x-input-field type="JSON"
                :column-types="[
                    'Wariant' => 'text',
                    'Obrazek' => 'url',
                ]"
                label="Obrazki wariantów"
                name="variants"
                :value="$attribute?->variants"
            />

            <div class="flex right center">
                @foreach ($attribute?->allVariantsForTiles() ?? [] as $variant)
                <x-variant-tile :variant="$variant" />
                @endforeach
            </div>
        </x-magazyn-section>
    </div>

    <div class="section flex right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($attribute)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
        <a class="button" href="{{ route('attributes') }}">Wróć</a>
    </div>
</form>

@endsection
