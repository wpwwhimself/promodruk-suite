@extends("layouts.main")
@section("title", "Podsumowanie Twojego zapytania")

@section("content")

@if (count($cart))

<form action="{{ route('mod-cart') }}" method="post" enctype="multipart/form-data" class="flex-down">
    @csrf

    <x-listing>
        @foreach ($cart as $item)
        <x-listing.cart-item :product="$item['product']">
            @foreach ($item["attributes"] as ["attr" => $attr, "var" => $var])
            <x-input-field type="text" name="" :label="$attr['name']" :value="$var['name']" disabled />
            @endforeach
            <x-input-field type="dummy" name="amounts[{{ $item['no'] }}]" label="Liczba szt." :value="$item['amount']" click-to-edit />
            <x-input-field type="TEXT" name="amounts[{{ $item['no'] }}]" label="Liczba szt." :value="$item['amount']" rows="2" class="hidden" onchange="this.form.submit()" />

            <x-input-field type="dummy" label="Komentarz" name="comments[{{ $item['no'] }}]" :value="$item['comment']" click-to-edit />
            <x-input-field type="TEXT" label="Komentarz" name="comments[{{ $item['no'] }}]" :value="$item['comment']" class="hidden" onchange="this.form.submit()" />

            <div class="flex-down center">
                <x-button action="none" label="Dodaj plik" icon="plus" onclick="event.preventDefault(); document.querySelector(`input[name='files[{{ $item['no'] }}][]']`).click()" />
                <x-input-field type="file" label="Pliki do zapytania" name="files[{{ $item['no'] }}][]" multiple onchange="this.form.submit()" class="hidden" />
                <div class="flex-down">
                    <input type="hidden" name="current_files[{{ $item['no'] }}]" value="{{ implode(",", $item["attachments"]) }}">
                    @foreach ($item["attachments"] as $file)
                    <span data-no="{{ $item['no'] }}" data-file="{{ $file }}" class="grid" style="grid-template-columns: 1fr 3em;">
                        <x-button :action="Storage::url($file)" target="_blank" icon="file" :label="basename($file)" />
                        <x-button action="none" onclick="deleteFile({{ $item['no'] }}, '{{ $file }}')" icon="delete" class="danger" />
                    </span>
                    @endforeach
                </div>
            </div>

            <x-slot:buttons>
                <x-button action="submit" name="delete" value="{{ $item['no'] }}" label="Usuń" icon="delete" />
            </x-slot:buttons>
        </x-listing.cart-item>
        @endforeach
    </x-listing>

    <script>
    const deleteFile = (no, file) => {
        const currentFilesInput = document.querySelector(`[name="current_files[${no}]"]`)
        let currentFiles = currentFilesInput.value.split(",")
        currentFiles.splice(currentFiles.indexOf(file), 1)
        currentFilesInput.value = currentFiles.join(",")

        // document.querySelector(`[data-no="${no}"][data-file="${file}"]`).remove()
        document.querySelector(`form[action="{{ route('mod-cart') }}"]`).submit()
    }
    </script>

    {{-- <div class="flex-right center">
        <x-button action="submit" name="save" value="1" label="Zapisz" icon="save" />
    </div> --}}
</form>

<h2>Dane kontaktowe</h2>

<div class="flex-down center">
<form action="{{ route('send-query') }}" method="post" enctype="multipart/form-data" style="width: 100%; max-width: 500px;">
    @csrf

    <x-input-field name="company_name" label="Nazwa firmy" />
    <x-input-field type="email" name="email_address" label="Adres e-mail" />
    <x-input-field name="client_name" label="Osoba kontaktowa" />
    <x-input-field type="tel" name="phone_number" label="Numer telefonu" />
    <x-multi-input-field name="supervisor_id" label="Wybierz opiekuna" :options="$supervisors" />
    <x-input-field type="TEXT" name="final_comment" label="Komentarz" />

    <div class="flex-right center">
        <x-button action="submit" label="Wyslij zapytanie" icon="send" class="danger" />
    </div>

</form>
</div>

@else

<p>Koszyk jest pusty. Przejdź do katalogu i wybierz produkty.</p>

<div class="flex-right center">
    <x-button :action="route('home')" label="Produkty" icon="arrow-left" />
</div>

@endif

@endsection
