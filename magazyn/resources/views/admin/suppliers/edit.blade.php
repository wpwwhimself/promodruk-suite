@extends("layouts.admin")
@section("title", "Edycja dostawcy")

@section("content")

<form action="{{ route('update-suppliers') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $supplier?->id }}">

    <div class="grid" style="--col-count: 2">
        <x-magazyn-section title="Dane dostawcy">
            <div class="grid" style="--col-count: 2">
                <x-input-field type="text" label="Nazwa" name="name" :value="$supplier?->name" required />
                <x-input-field type="text" label="Prefiks" name="prefix" :value="$supplier?->prefix" required />
            </div>

            <x-input-field type="TEXT" name="notes" label="Notatka" :value="$supplier?->notes" />
        </x-magazyn-section>

        <x-magazyn-section title="Kategorie">
            <p>Lista kategorii, jakie produkty tego dostawcy mogą posiadać</p>

            <x-app.loader text="Przetwarzanie" />
            <x-suppliers.categories-editor :items="$supplier?->categories" />
            <script src="{{ asset("js/supplier-categories-editor.js") }}" defer>
            toggleLoader()
            </script>
        </x-magazyn-section>
    </div>

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($supplier)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
        <a class="button" href="{{ route('suppliers') }}">Wróć</a>
    </div>
</form>

@endsection
