@extends("layouts.admin")
@section("title", implode(" | ", ["Generuj warianty", $family->name]))

@section("content")

<form action="{{ route('product-generate-variants-process') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="family_id" value="{{ $family->id }}">

    <p class="ghost">
        W tym panelu możesz wygenerować warianty produktu z puli utworzonych cech.
        Pozwala to np. na szybkie utworzenie wielu kolorów jednego produktu.
    </p>

    <div class="grid" style="--col-count: 2;">
        <x-magazyn-section title="Cechy">
            <x-multi-input-field
                label="Wybierz cechy, dla których chcesz utworzyć warianty"
                name="alt_attribute_id"
                :options="$altAttributes->pluck('id', 'name')"
                empty-option="Kolory (domyślne)"
                :value="request('alt_attribute_id') ?? $family->alt_attribute_id"
                onchange="window.location.href = `?alt_attribute_id=${event.target.value || 0}`;"
            />
        </x-magazyn-section>

        <x-magazyn-section title="Warianty">
            @foreach ($variants as $variant)
            <div class="flex-right middle">
                @isset ($variant["selected"])
                <x-variant-tile :variant="$variant" />
                <span>{{ $variant["selected"]["label"] }}</span>
                <input type="checkbox"
                    name="variants[]"
                    value="{{ $variant["selected"]["label"] }}"
                    @if ($family->products->firstWhere("variant_name", $variant["selected"]["label"])) checked @endif
                />

                @else
                <span>{{ $variant->id }}</span>
                <x-variant-tile :color="$variant" />
                <span>{{ $variant->name }}</span>
                <input type="checkbox"
                    name="variants[]"
                    value="{{ $variant->name }}"
                    @if ($family->products->firstWhere("variant_name", $variant->name)) checked @endif
                />
                @endisset
            </div>
            @endforeach
        </x-magazyn-section>
    </div>

    <div class="section flex-down middle">
        <span class="danger">Uwaga! Operacja zresetuje dane wszystkich istniejących wariantów produktu.</span>

        <div class="flex-right center">
            <button type="submit" name="mode" value="save" class="danger">Zapisz</button>
            <a class="button" href="{{ route('products-edit-family', ['id' => $family->prefixed_id]) }}">Wróć</a>
        </div>
    </div>
</form>

@endsection
