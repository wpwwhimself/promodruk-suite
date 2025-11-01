@extends("layouts.admin")
@section("title", implode(" | ", ["Generuj warianty", $family->name]))

@section("content")

<x-shipyard.app.form :action="route('product-generate-variants-process')" method="post" class="flex down">
    <input type="hidden" name="family_id" value="{{ $family->id }}">

    <p class="ghost">
        W tym panelu możesz wygenerować warianty produktu z puli utworzonych cech.
        Pozwala to np. na szybkie utworzenie wielu kolorów jednego produktu.
    </p>

    <x-magazyn-section title="Warianty" :icon="model_icon('products')">
        <p>
            Lista dostępnych wariantów na podstawie cechy:
            <b>{{ $family->alt_attributes["name"] ?? "Kolory" }}</b>
        </p>
        @foreach ($variants as $variant)
        <div class="input-container">
            @isset ($variant["selected"])
            <span role="label-wrapper">
                <x-variant-tile :variant="$variant" />
                <span>{{ $variant["selected"]["label"] }}</span>
            </span>
            <input type="checkbox"
                name="variants[]"
                value="{{ $variant["selected"]["label"] }}"
                @if ($family->products->firstWhere("variant_name", $variant["selected"]["label"])) checked @endif
            />

            @else
            <span role="label-wrapper">
                <span>{{ $variant->id }}</span>
                <x-variant-tile :color="$variant" />
                <span>{{ $variant->name }}</span>
            </span>
            <input type="checkbox"
                name="variants[]"
                value="{{ $variant->name }}"
                @if ($family->products->firstWhere("variant_name", $variant->name)) checked @endif
            />
            @endisset
        </div>
        @endforeach
    </x-magazyn-section>

    <x-slot:actions>
        <span class="danger">Uwaga! Operacja zresetuje dane wszystkich istniejących wariantów produktu.</span>

        <div class="flex right center">
            <x-shipyard.ui.button action="submit" name="mode" value="save" class="danger" label="Zapisz" icon="check" />
            <x-shipyard.ui.button :action="route('products-edit-family', ['id' => $family->prefixed_id])" label="Wróć" icon="arrow-left" />
        </div>
    </x-slot:actions>
</x-shipyard.app.form>

@endsection
