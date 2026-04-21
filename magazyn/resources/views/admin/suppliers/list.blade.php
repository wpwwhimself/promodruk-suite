@extends("layouts.admin")
@section("title", "Dostawcy")

@section("content")

<x-shipyard.app.section title="Dostawcy niestandardowi" :icon="model_icon('custom-suppliers')">
    <x-slot:actions>
        <x-shipyard.ui.button
            :action="route('suppliers-edit')"
            icon="plus"
            label="Dodaj"
            class="primary"
        />
    </x-slot:actions>

    <p>
        Dostawcy, których można przypisać do <strong>produktów własnych</strong>.
    </p>

    <div class="grid" style="--col-count: 3">
        @forelse ($custom_suppliers as $supplier)
        <x-suppliers.tile :supplier="$supplier" />
        @empty
        <p class="ghost">Brak dostawców niestandardowych</p>
        @endforelse
    </div>
</x-shipyard.app.section>

<x-shipyard.app.section title="Dostawcy z synchronizacji" :icon="model_icon('product-synchronizations')">
    <p>
        Dostawcy, dla których skonfigurowano automatyczne pobieranie informacji o produktach, stanach magazynowych i znakowaniach.
        Edycja tej listy nie jest możliwa.
    </p>

    <div class="grid" style="--col-count: 3">
        @foreach ($sync_suppliers as $supplier)
        <x-suppliers.tile :supplier="$supplier" :editable="false" />
        @endforeach
    </div>
</x-shipyard.app.section>

@endsection
