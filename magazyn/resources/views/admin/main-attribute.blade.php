@extends("layouts.admin")
@section("title", implode(" | ", [$attribute->name ?? "Nowa cecha podstawowa", "Edycja cechy podstawowej"]))

@section("content")

<x-shipyard.app.form action="{{ route('update-main-attributes') }}" method="post" class="flex down">
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <div class="grid" style="--col-count: 2">
        <x-magazyn-section title="Podstawowe informacje" :icon="model_icon('main-attributes')">
            <x-input-field type="text" label="Nazwa oryginalna" name="name" :value="$attribute?->name" disabled />

            @if ($productExamples->count())
            <h2>Produkty z o tym kolorze</h2>
            <div>
                @foreach ($productExamples as $source => $examples)
                <h3>{{ $source }}</h3>
                <div class="flex right nowrap scrollable horizontally">
                    @foreach ($examples as $i => $product)
                    @if ($i > 20)
                        <span>... i jeszcze {{ count($examples) - 20 }} pozycji</span>
                        @break
                    @endif
                    <x-attributes.product-highlight :product="$product" />
                    @endforeach
                </div>
                @endforeach
            </div>
            @else
            <p class="ghost">Brak produktów o tym kolorze</p>
            @endif
        </x-magazyn-section>

        <x-magazyn-section title="Powiązanie z kolorem nadrzędnym" :icon="model_icon('primary-colors')">
            <x-slot:buttons>
                <x-button :action="route('primary-color-edit')" target="_blank" label="Utwórz nowy kolor" onclick="primeReload()" />
            </x-slot:buttons>

            @if ($attribute?->primary_color_id)
            @php $pcl = $primaryColors->firstWhere("id", $attribute?->primary_color_id) @endphp
            <h2 class="flex right middle" style="grid-column: span 3;">
                Ten kolor jest podrzędny do koloru:
                {{ $pcl->name }}
                <x-variant-tile :color="$pcl" />
            </h2>
            @endif

            <div class="color-type-container grid scrollable horizontally" style="--col-count: 3;">
                @foreach ($primaryColors as $pcl)
                <div class="input-container">
                    <span role="label-wrapper">
                        <label for="primary_color_id">{{ $pcl->name }}</label>
                        <x-variant-tile :color="$pcl" />
                    </span>
                    <input type="radio" id="primary_color_id" name="primary_color_id" value="{{ $pcl->id }}" {{ $attribute?->primary_color_id == $pcl->id ? "checked" : "" }} />
                </div>
                @endforeach
            </div>

            <script>
            function primeReload() {
                document.querySelector("#loader").classList.remove("hidden")
                window.onfocus = function () { location.reload(true) }
            }
            </script>
        </x-magazyn-section>
    </div>

    <x-slot:actions>
        <x-shipyard.ui.button action="submit" name="mode" value="save" label="Zapisz" icon="check" class="primary" />
        @if ($attribute)
        <x-shipyard.ui.button action="submit" name="mode" value="delete" class="danger" label="Usuń" icon="delete" />
        @endif
        <x-shipyard.ui.button :action="route('attributes')" label="Wróć" icon="arrow-left" />
    </x-slot:actions>
</x-shipyard.app.form>

<x-app.loader text="Poczekaj na wczytanie zmian" />

<style>
.input-container.hidden {
    display: none;
}
</style>

@endsection
