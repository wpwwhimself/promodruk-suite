@extends("layouts.shipyard.admin")
@section("title", "Import produktów")

@section("content")

<x-shipyard.app.card
    title="Wybierz tryb importu"
>
    <div class="flex right center middle">
        <x-shipyard.ui.button
            :action="route('products-import-init')"
            label="Standardowy (po dostawcach)"
            pop="Na podstawie wybranego dostawcy i jego kategorii"
            icon="truck"
            class="primary"
        />
        <x-shipyard.app.form :action="route('products-import-fetch')" method="post">
            <x-shipyard.ui.button action="submit"
                label="Wszystkie nowości"
                pop="Wszystkie dostępne produkty oznaczone przez dostawców jako nowości"
                icon="new-box"
                class="primary"
                name="all_marked_as_new"
                value="1"
            />
        </x-shipyard.app.form>
        <x-shipyard.ui.button
            :action="route('products-import-init-missing')"
            label="Brakujące produkty"
            pop="Wszystkie produkty spośród kategorii dostawców, których nie ma w Ofertowniku"
            icon="help-box-outline"
            class="primary"
        />

        <x-shipyard.ui.button
            :action="route('admin.model.list', ['model' => 'products'])"
            pop="Wróć"
            icon="arrow-left"
        />
    </div>
</x-shipyard.app.card>

@endsection
