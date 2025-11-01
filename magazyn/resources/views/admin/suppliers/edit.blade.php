@extends("layouts.admin")
@section("title", "Edycja dostawcy")

@section("content")

<x-shipyard.app.form action="{{ route('update-suppliers') }}" method="post">
    <input type="hidden" name="id" value="{{ $supplier?->id }}">

    <div class="grid" style="--col-count: 2">
        <x-magazyn-section title="Dane dostawcy" :icon="model_icon('custom-suppliers')">
            <div class="flex down">
                <div class="grid" style="--col-count: 2">
                    <x-input-field type="text" label="Nazwa" name="name" :value="$supplier?->name" required />
                    <x-input-field type="text" label="Prefiks" name="prefix" :value="$supplier?->prefix" required />
                </div>

                <x-input-field type="TEXT" name="notes" label="Notatka" :value="$supplier?->notes" />
            </div>
        </x-magazyn-section>

        <x-magazyn-section title="Kategorie" icon="wardrobe">
            <p>Lista kategorii, jakie produkty tego dostawcy mogą posiadać</p>

            <x-app.loader text="Przetwarzanie" />
            <x-suppliers.categories-editor :items="$supplier?->categories" />
            <script src="{{ asset("js/supplier-categories-editor.js") }}" defer>
            toggleLoader()
            </script>
        </x-magazyn-section>
    </div>

    <x-slot:actions>
        <x-shipyard.ui.button action="submit"
            name="mode" value="save"
            label="Zapisz" icon="check"
        />
        @if ($supplier)
        <x-shipyard.ui.button action="submit"
            name="mode" value="delete"
            class="danger"
            label="Usuń" icon="delete"
        />
        @endif
        <x-shipyard.ui.button
            :action="route('suppliers')"
            label="Wróć" icon="arrow-left"
        />
    </x-slot:actions>
</x-shipyard.app.form>

@endsection
