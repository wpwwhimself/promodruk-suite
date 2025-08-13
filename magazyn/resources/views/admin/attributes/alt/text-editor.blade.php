@extends("layouts.admin")
@section("title", "Generator tekstu na obrazku wariantu")

@section("content")

<div class="grid" style="--col-count: 2">
    <x-magazyn-section title="Parametry">
        <form onsubmit="process(event)">
            <x-input-field type="text"
                name="text"
                label="Tekst do wyświetlenia"
            />
            <x-input-field type="text"
                name="font"
                label="Czcionka"
                value="Arial"
            />
            <x-input-field type="number" min="1"
                name="font_size"
                label="Rozmiar czcionki [pt]"
                value="12"
            />
            <x-input-field type="color"
                name="color"
                label="Kolor tekstu"
                value="#000000"
            />
            <x-input-field type="color"
                name="bg_color"
                label="Kolor tła"
                value="#ffffff"
            />

            <x-input-field type="checkbox"
                name="bold"
                label="Pogrubienie"
            />
            <x-input-field type="checkbox"
                name="italic"
                label="Kursywa"
            />
            <x-input-field type="checkbox"
                name="underline"
                label="Podkreslenie"
            />

            <div class="flex-right center">
                <button class="button" type="submit">Podgląd</button>
            </div>
        </form>
    </x-magazyn-section>

    <x-magazyn-section title="Podgląd">
        <div id="preview" class="flex-right center"></div>

        <x-input-field type="text"
            name="output"
            label="Kod kafelka"
            onclick="event.target.select()"
        />

        <p>
            Skopiuj powyższy kod i wklej go jako obrazek wariantu w produkcie, który edytujesz, żeby zastosować zmiany.
        </p>
    </x-magazyn-section>
</div>

<script>
function process(ev) {
    ev.preventDefault();

    const formData = new FormData(ev.target);
    let textParams = {};
    formData.forEach((v, k) => textParams[k] = v);

    const data = {
        code: `@txt@${JSON.stringify(textParams)}`,
    };

    fetch(`{{ route('alt-attributes-text-editor-test-tile') }}?` + new URLSearchParams(data))
        .then(res => res.text())
        .then(tile => {
            document.querySelector("#preview").innerHTML = tile;
            document.querySelector("[name=output]").value = data.code;
        });
}
process(new Event("submit"));
</script>

@endsection
