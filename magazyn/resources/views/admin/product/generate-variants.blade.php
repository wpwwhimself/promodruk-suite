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

    <x-magazyn-section title="Warianty">
        <p>
            Lista dostępnych wariantów na podstawie cechy:
            <b>{{ $family->alt_attributes["name"] ?? "Kolory" }}</b>
        </p>
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

    <div class="section flex-down middle">
        <span class="danger">Uwaga! Operacja zresetuje dane wszystkich istniejących wariantów produktu.</span>

        <div class="flex-right center">
            <button type="submit" name="mode" value="save" class="danger">Zapisz</button>
            <a class="button" href="{{ route('products-edit-family', ['id' => $family->prefixed_id]) }}">Wróć</a>
        </div>
    </div>
</form>

@endsection
