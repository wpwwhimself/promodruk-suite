@extends("layouts.admin")
@section("title", implode(" | ", [$entity->name, "Import specyfikacji"]))

@section("content")

<form action="{{ route('products-import-specs-process') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="entity_name" value="{{ $entity::class }}">
    <input type="hidden" name="id" value="{{ $entity->id }}">

    <x-magazyn-section title="Specyfikacja">
        <x-ckeditor
            name="specs_raw"
            label="Tutaj wklej tabelę specyfikacji"
            :value="$specs_raw ?? null"
        />

        <div class="flex-right center">
            <button type="submit" name="mode" value="process">Przetwórz</button>
        </div>
    </x-magazyn-section>

    @isset ($tabs)
    <x-magazyn-section title="Zakładki">
        <x-product.tabs-editor :tabs="$tabs" :editable="false" />
    </x-magazyn-section>
    @endisset

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        <x-button :action="route(
            Str::of($entity::class)->contains('ProductFamily') ? 'products-edit-family' : 'products-edit',
            ['id' => Str::of($entity::class)->contains('ProductFamily') ? $entity->prefixed_id : $entity->front_id]
        )" label="Wróć" />
    </div>
</form>

@endsection
