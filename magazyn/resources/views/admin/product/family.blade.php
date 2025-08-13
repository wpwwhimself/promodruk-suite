@extends("layouts.admin")
@section("title", implode(" | ", [$family->name ?? "Nowa rodzina produktów", "Edycja rodziny produktów"]))

@php
use App\Http\Controllers\AdminController;
@endphp

@section("content")

@if (!$isCustom) <span class="ghost">Produkt <strong>{{ $family?->name }}</strong> został zaimportowany od zewnętrznego dostawcy i części jego parametrów nie można edytować</span> @endif

<x-app.loader text="Przetwarzanie" />

<form action="{{ route('update-product-families') }}" method="post" class="flex-down" enctype="multipart/form-data">
    @csrf

    @if (!$isCustom) <input type="hidden" name="id" value="{{ $family?->id }}"> @endif
    <input type="hidden" name="_model" value="App\Models\ProductFamily">

    <x-magazyn-section title="Rodzina">
        <x-slot:buttons>
            @if ($family && $isCustom)
            <x-button
                label="Kopiuj na nową rodzinę"
                :action="route('products-edit-family', ['copy_from' => $family->id])"
                target="_blank"
            />
            @endif
        </x-slot:buttons>

        <div class="grid" style="--col-count: {{ 2 + !!$family }}">
            <x-input-field type="text" label="Nazwa" name="name" :value="$copyFrom->name ?? $family?->name" :disabled="!$isCustom" required />
            <x-input-field type="text" label="Podtytuł" name="subtitle" :value="$copyFrom->subtitle ?? $family?->subtitle" :disabled="!$isCustom" />
            @if ($family)
            <input type="hidden" name="id" value="{{ $family->id }}" />
            <x-input-field type="text" label="SKU" name="_prefixed_id" :value="$isCustom ? $family->prefixed_id : $family->id" disabled />
            @endif
        </div>

        <div class="grid" style="--col-count: 2">
            <x-multi-input-field
                label="Pochodzenie (dostawca)" name="source"
                :options="$suppliers"
                :value="$family ? Str::after($family->source, App\Models\ProductFamily::CUSTOM_PRODUCT_GIVEAWAY) : null" empty-option="wybierz"
                :required="$isCustom"
                :disabled="!$isCustom"
                onchange="loadCategories(event.target.value)"
            />
            <script src="{{ asset("js/supplier-categories-selector.js") }}" defer></script>

            <x-suppliers.categories-selector :items="$categories" :value="$copyFrom->original_category ?? $family?->original_category" :editable="$isCustom" />
        </div>
    </x-magazyn-section>

    <x-magazyn-section title="Opis">
        <div class="grid" style="--col-count: 2">
            <p class="ghost">
                W <strong>Ofertowniku</strong> treść wpisana w polu poniżej będzie poprzedzona tekstem <strong>{{ $copyFrom->description_label ?? $family?->description_label ?? "Opis" }}:</strong>
                <br>
                Jeśli chcesz to zmienić, podaj nową etykietę opisu.
            </p>

            <x-input-field type="text"
                name="description_label"
                label="Etykieta opisu"
                :value="$copyFrom->description_label ?? $family?->description_label"
                placeholder="Opis"
                :disabled="!$isCustom"
            />
        </div>

        <x-ckeditor label="Treść" name="description" :value="$copyFrom->description ?? $family?->description" :disabled="!$isCustom" />
    </x-magazyn-section>

    <div class="grid" style="--col-count: 2">
        @if ($family)

        <x-magazyn-section title="Warianty">
            <x-slot:buttons>
                @if ($isCustom)
                <x-button
                    label="Generuj dla cech"
                    :action="route('product-generate-variants', ['family_id' => $family->id])"
                />
                <x-button
                    label="Nowy"
                    :action="route('products-edit', ['copy_from' => $family->id])"
                />
                @endif
            </x-slot:buttons>

            {{-- alt attributes --}}
            <x-input-field type="checkbox"
                name="enable_alt_attributes"
                label="Warianty niestandardowe aktywne"
                :value="$family->alt_attributes != null"
                :disabled="!$isCustom"
                onchange="toggleAltAttributesEditor(event)"
            />

            <div role="alt_attributes_container" {{ $family->alt_attributes == null ? "class=hidden" : "" }}>
                <x-input-field type="text"
                    label="Warianty reprezentują:"
                    name="alt_attributes[name]"
                    placeholder="np. Kolory, Formaty, ..."
                    :value="$family->alt_attributes['name'] ?? null"
                />
                <x-input-field type="checkbox"
                    label="Duże kafelki"
                    name="alt_attributes[large_tiles]"
                    :value="$family->alt_attributes['large_tiles'] ?? null"
                />
                <x-input-field type="JSON"
                    :column-types="[
                        'Wariant' => 'text',
                        'Obrazek/Dane tekstowe' => 'text',
                    ]"
                    label="Obrazki wariantów"
                    name="alt_attributes[variants]"
                    :value="$family->alt_attributes['variants'] ?? null"
                />

                <div class="flex-right center">
                    <x-button :action="route('alt-attributes-text-editor')" label="Generator tekstu na obrazku" target="_blank"/>
                </div>

                <div class="flex-right center">
                    @foreach ($family->alt_attribute_tiles as $variant)
                    <x-variant-tile :variant="$variant" />
                    @endforeach
                </div>
            </div>

            <script>
            function toggleAltAttributesEditor(ev) {
                document.querySelector("[role=alt_attributes_container]").classList.toggle("hidden", !ev.target.checked)
            }
            </script>

            <h3>Utworzone warianty</h3>

            <div class="grid" style="--col-count: 2">
                @forelse ($family->products as $product)
                <div>
                    <x-product.tile :product="$product" />
                </div>
                @empty
                <p class="ghost">Brak wariantów</p>
                @endforelse
            </div>
        </x-magazyn-section>

        <x-magazyn-section title="Zdjęcia">
            <x-slot:buttons>
                @if ($isCustom)
                <x-button :action="route('files')" label="Wgraj nowe" target="_blank" />
                @endif
            </x-slot:buttons>

            <p class="ghost">
                Wspólne zdjęcia dla wszystkich produktów w tej rodzinie.
                Na widoku produktu zdjęcia pojawią się w następującej kolejności:
            </p>
            <ol class="ghost">
                <li>zdjęcia rodziny oznaczone jako <em>Okładka</em> (wg kolejności)</li>
                <li>zdjęcia wariantu (w kolejności)</li>
                <li>pozostałe zdjęcia rodziny (w kolejności)</li>
            </ol>
            <p class="ghost">
                Pierwsze zdjęcie z powyższej listy będzie traktowane jako okładka i pojawi się w kafelku produktu.
            </p>

            <div class="flex-right">
                @foreach ($family->images as $img)
                <img class="thumbnail" src="{{ url($img) }}" />
                @endforeach
            </div>

            <div class="flex-right">
                <x-input-field type="JSON"
                    name="image_urls" label="Zdjęcia"
                    :column-types="[
                        'Kolejność' => 'number',
                        'Ścieżka' => 'url',
                        'Okładka' => 'checkbox',
                    ]"
                    :disabled="!$isCustom"
                    :value="$family->image_urls"
                />
            </div>

            {{-- disabled editing manually
            <h3>Miniatury</h3>
            <p class="ghost">
                Pomniejszone zdjęcia, które wyświetlają się zamiast głównych zdjęć w miejscach takich jak galeria zdjęć, aby przyspieszyć ich ładowanie dla użytkownika.
                <strong>N-ta miniatura jest powiązana z n-tym ze zdjęć.</strong>
                Zdjęcia te są wyświetlane alfabetycznie.
                Brak wgranych miniatur sprawia, że wyświetlane są zdjęcia w pełnej rozdzielczości.
            </p>

            <input type="hidden" name="thumbnails" value="{{ $family->thumbnails ? $family->thumbnails->join(",") : "" }}">
            <table class="thumbnails">
                <thead>
                    <tr>
                        <th>Zdjęcie</th>
                        <th>Nazwa</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                @if ($family->thumbnails)
                @foreach ($family->thumbnails->filter(fn($img) => $img) as $img)
                    <tr attr-name="{{ $img }}">
                        <td><img class="inline" src="{{ url($img) }}" {{ Popper::pop("<img class='thumbnail' src='".url($img)."' />") }} /></td>
                        <td>{{ basename($img) }}</td>
                        <td>
                            @if (Str::startsWith($img, env("APP_URL")) && $isCustom)
                            <span class="clickable" onclick="deleteThumbnail(this)">Usuń</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                @if ($isCustom)
                <tfoot>
                    <tr>
                        <td colspan=3><x-input-field type="file" label="Dodaj zdjęcia" name="newThumbnails[]" multiple onchange="submitForm()" /></td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <script>
            const deleteThumbnail = (btn) => {
                let ids = document.querySelector("input[name=thumbnails]").value.split(",")
                ids = ids.filter(id => id != btn.closest("tr").getAttribute("attr-name"))
                document.querySelector("input[name=thumbnails]").value = ids.join(",")

                btn.closest("tr").remove()
                submitForm()
            }
            </script>
            --}}
        </x-magazyn-section>

        @endif
    </div>

    @if ($family)
    <x-magazyn-section title="Zakładki">
        <x-slot:buttons>
            @if ($isCustom)
            <x-button :action="route('products-import-specs', ['entity_name' => 'ProductFamily', 'id' => $family->id])" label="Importuj tabelę specyfikacji" />
            <span class="button" onclick="newTab()">Dodaj nową zakładkę</span>
            @endif
        </x-slot:buttons>

        <x-product.tabs-editor :tabs="$family->tabs" :editable="$isCustom" />
        <script src="{{ asset("js/tabs-editor.js") }}" defer></script>
    </x-magazyn-section>
    @endif

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($family)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
        <a class="button" href="{{ route('products') }}">Wróć</a>
    </div>
</form>

@endsection
