@extends("layouts.shipyard.admin")
@section("title", "Import braków")
@section("subtitle", "Import produktów")

@section("content")

<x-shipyard.app.card>
    <p>Import pozwala na dodanie do Ofertownika podobnych i jeszcze niepobranych z Magazynu produktów.</p>
</x-shipyard.app.card>

<x-shipyard.app.form :action="route('products-import-fetch')" method="post" enctype="multipart/form-data">
    <input type="hidden" name="missing_mode" value="1">

    <x-shipyard.app.card
        title="Parametry importu"
        subtitle="Podaj informacje o produktach"
        icon="help-box-outline"
    >
        <x-shipyard.ui.input type="text"
            name="category"
            label="Fraza w kategorii dostawcy"
            icon="tag"
            hint="Wpisz zwrot, jaki może znaleźć się w kategorii dostawcy, np. 'długopisy metalowe'. Wielkość liter nie ma znaczenia."
        />
    </x-shipyard.app.card>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button action="submit"
                label="Znajdź"
                icon="magnify"
                class="primary"
            />
            <x-shipyard.ui.button :action="route('products-import-mode')"
                label="Od nowa"
                icon="restart"
            />
            <x-shipyard.ui.button :action="route('admin.model.list', ['model' => 'products'])"
                label="Porzuć i wróć"
                icon="arrow-left"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>

@endsection
