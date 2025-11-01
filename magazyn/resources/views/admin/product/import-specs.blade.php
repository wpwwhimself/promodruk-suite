@extends("layouts.admin")
@section("title", implode(" | ", [$entity->name, "Import specyfikacji"]))

@section("content")

<x-shipyard.app.form :action="route('products-import-specs-process')" method="post" class="flex down">
    <input type="hidden" name="entity_name" value="{{ $entity::class }}">
    <input type="hidden" name="id" value="{{ $entity->id }}">

    <x-magazyn-section title="Specyfikacja" icon="table">
        <x-shipyard.ui.input type="HTML"
            name="specs_raw"
            label="Tutaj wklej tabelę specyfikacji"
            :value="$specs_raw ?? null"
        />

        <x-slot:buttons>
            <x-shipyard.ui.button action="submit" name="mode" value="process" label="Przetworz" icon="check" class="primary" />
        </x-slot:buttons>
    </x-magazyn-section>

    @isset ($tabs)
    <x-magazyn-section title="Zakładki" icon="tab">
        <x-product.tabs-editor :tabs="$tabs" :editable="false" />
    </x-magazyn-section>
    @endisset

    <x-slot:actions>
        <x-shipyard.ui.button action="submit" name="mode" value="save" label="Zapisz" icon="check" class="primary" />
        <x-button :action="route(
            Str::of($entity::class)->contains('ProductFamily') ? 'products-edit-family' : 'products-edit',
            ['id' => Str::of($entity::class)->contains('ProductFamily') ? $entity->prefixed_id : $entity->front_id]
        )" label="Wróć" icon="arrow-left" />
    </x-slot:actions>
</x-shipyard.app.form>

@endsection
