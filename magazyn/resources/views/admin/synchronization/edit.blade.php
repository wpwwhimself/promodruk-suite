@extends("layouts.admin")
@section("title", implode(" | ", [$synchronization->supplier_name, "Edycja synchronizacji"]))

@section("content")

<form action="{{ route('update-synchronizations') }}" method="post" class="flex down">
    @csrf
    <input type="hidden" name="supplier_name" value="{{ $synchronization->supplier_name }}">

    <x-magazyn-section title="Właściwości synchronizacji">
        <x-multi-input-field
            name="quickness_priority"
            label="Priorytet ogólny"
            :options="$quicknessPriorities"
            :value="$synchronization->quickness_priority"
        />

        <h3>Priorytety modułów importu</h3>
        <p>
            Pozwala na sterowanie kolejnością pobierania danych.
            Przydatne w przypadku, jeśli np. konieczne jest pobranie stanów magazynowych przed pobraniem produktów.
        </p>

        @foreach ([
            ["product_import_enabled", "Produkty"],
            ["stock_import_enabled", "Stany magazynowe"],
            ["marking_import_enabled", "Znakowania"],
        ] as [$name, $label])
        <x-multi-input-field
            :name="$name"
            :label="$label"
            :options="$modulePriorities"
            :value="$synchronization->$name"
        />
        @endforeach
    </x-magazyn-section>

    <div class="section flex right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        <a class="button" href="{{ route('synchronizations') }}">Wróć</a>
    </div>
</form>

@endsection
