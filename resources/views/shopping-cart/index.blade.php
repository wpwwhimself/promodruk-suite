@extends("layouts.main")
@section("title", "Podsumowanie Twojego zapytania")

@section("content")

@if (count($cart["positions"]))

<form action="{{ route('mod-cart') }}" method="post" enctype="multipart/form-data" class="flex-down">
    @csrf

    <h2>Wspólne załączniki</h2>
    <p>
        Tutaj możesz dodać wspólny plik (pliki) np. z logo, dla wszystkich produktów z zapytania.
        Poniżej można dodać plik (pliki) dla danego produktu z osobna.
    </p>

    <div class="flex-down files">
        <div class="flex-right">
            <x-button action="none" label="Dodaj plik" icon="plus" onclick="document.querySelector(`input[name='global_files[]']`).click()" />
            <x-cart-file-hint />
        </div>

        <x-input-field type="file" label="Pliki do zapytania" name="global_files[]" multiple onchange="this.form.submit()" class="hidden" />
        <div class="flex-down">
            <input type="hidden" name="current_global_files" value="{{ implode(",", $cart["global_attachments"]) }}">
            @foreach ($cart["global_attachments"] as $file)
            <span data-file="{{ $file }}" class="grid" style="grid-template-columns: 1fr 3em; align-items: center;">
                <a href="{{ Storage::url($file) }}" target="_blank">{{ basename($file) }}</a>
                <x-button action="none" onclick="deleteFile(null, '{{ $file }}')" icon="delete" class="danger sleek" label="Usuń" hide-label />
            </span>
            @endforeach
        </div>
    </div>

    <h2>Pozycje zapytania</h2>

    <x-listing>
        @foreach ($cart["positions"] as $item)
        <x-listing.cart-item :product="$item['product']">
            @foreach ($item["attributes"] as ["attr" => $attr, "var" => $var])
            <x-input-field type="text" name="" :label="$attr['name']" :value="$var['name']" disabled />
            @endforeach
            <x-input-field type="dummy" name="amounts[{{ $item['no'] }}]" label="Liczba szt." :value="$item['amount']" click-to-edit />
            <x-input-field type="TEXT" name="amounts[{{ $item['no'] }}]" label="Liczba szt." :value="$item['amount']" rows="2" class="hidden" click-to-save />

            <x-input-field type="dummy" label="Komentarz" name="comments[{{ $item['no'] }}]" :value="$item['comment']" click-to-edit />
            <x-input-field type="TEXT" label="Komentarz" name="comments[{{ $item['no'] }}]" :value="$item['comment']" class="hidden" click-to-save />

            <div class="flex-down files">
                <div class="flex-right">
                    <x-button action="none" label="Dodaj plik" icon="plus" onclick="document.querySelector(`input[name='files[{{ $item['no'] }}][]']`).click()" />
                    <x-cart-file-hint />
                </div>
                <x-input-field type="file" label="Pliki do zapytania" name="files[{{ $item['no'] }}][]" multiple onchange="this.form.submit()" class="hidden" />
                <div class="flex-down">
                    <input type="hidden" name="current_files[{{ $item['no'] }}]" value="{{ implode(",", $item["attachments"]) }}">
                    @foreach ($item["attachments"] as $file)
                    <span data-no="{{ $item['no'] }}" data-file="{{ $file }}" class="grid" style="grid-template-columns: 1fr 3em; align-items: center;">
                        <a href="{{ Storage::url($file) }}" target="_blank">{{ basename($file) }}</a>
                        <x-button action="none" onclick="deleteFile({{ $item['no'] }}, '{{ $file }}')" icon="delete" class="danger sleek" label="Usuń" hide-label />
                    </span>
                    @endforeach
                </div>
            </div>

            <x-slot:buttons>
                <x-button action="submit" name="delete" value="{{ $item['no'] }}" label="Usuń" icon="delete" class="danger sleek" />
            </x-slot:buttons>
        </x-listing.cart-item>
        @endforeach
    </x-listing>

    <script>
    const deleteFile = (no, file) => {
        const currentFilesInput = document.querySelector(no === null ? `[name="current_global_files"]` : `[name="current_files[${no}]"]`)
        let currentFiles = currentFilesInput.value.split(",")
        currentFiles.splice(currentFiles.indexOf(file), 1)
        currentFilesInput.value = currentFiles.join(",")

        // document.querySelector(`[data-no="${no}"][data-file="${file}"]`).remove()
        document.querySelector(`form[action="{{ route('mod-cart') }}"]`).submit()
    }
    </script>

    {{-- <div class="flex-right center hidden hidden-save">
        <x-button action="submit" name="save" value="1" label="Zapisz" icon="save" />
    </div> --}}
</form>

<h2>Dane kontaktowe</h2>

<div class="flex-down">
<form action="{{ route('send-query') }}" method="post" enctype="multipart/form-data" style="width: 100%; max-width: 500px;">
    @csrf

    <x-input-field name="company_name" label="Nazwa firmy" required />
    <x-input-field type="email" name="email_address" label="Adres e-mail" required />
    <x-input-field name="client_name" label="Osoba kontaktowa" required />
    <x-input-field type="tel" name="phone_number" label="Numer telefonu" required />
    <x-multi-input-field name="supervisor_id" label="Wybierz opiekuna" :options="$supervisors" empty-option="wybierz..." required />
    <x-input-field type="TEXT" name="final_comment" label="Komentarz" />

    <div class="flex-right center">
        <x-button action="submit" label="Wyślij zapytanie" icon="send" />
    </div>

</form>
</div>

<style>
.cart-item button.danger[name="delete"] span {
    color: hsl(var(--fg));
}
</style>

@else

<p>Koszyk jest pusty. Przejdź do katalogu i wybierz produkty.</p>

<div class="flex-right center">
    <x-button :action="route('home')" label="Produkty" icon="arrow-left" />
</div>

@endif

@endsection
