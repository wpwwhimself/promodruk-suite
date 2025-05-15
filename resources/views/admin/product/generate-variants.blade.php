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

    <div class="grid" style="--col-count: 1;">
        <x-magazyn-section title="Kolory">
            @foreach ($colors as $color)
            <div class="flex-right middle">
                <span>{{ $color->id }}</span>
                <x-color-tag :color="$color" />
                <span>{{ $color->name }}</span>
                <input type="checkbox"
                    name="colors[]"
                    value="{{ $color->name }}"
                    @if ($family->products->firstWhere("original_color_name", $color->name)) checked @endif
                />
            </div>
            @endforeach
        </x-magazyn-section>
    </div>

    <div class="section flex-down middle">
        <span class="danger">Uwaga! Operacja zresetuje dane wszystkich istniejących wariantów produktu.</span>

        <div class="flex-right center middle">
            <x-input-field type="checkbox"
                name="illustrative"
                label="Warianty tylko poglądowo"
                :value="$family->has_illustrative_variants"
            />
            <span class="ghost">
                Użytkownik nie będzie w stanie wybrać konkretnego wariantu produktu w Ofertowniku,
                ale będzie widział "Dostępne warianty".
            </span>
        </div>

        <div class="flex-right center">
            <button type="submit" name="mode" value="save" class="danger">Zapisz</button>
            <a class="button" href="{{ route('products-edit-family', ['id' => $family->prefixed_id]) }}">Wróć</a>
        </div>
    </div>
</form>

@endsection
