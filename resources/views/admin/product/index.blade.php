@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@php
use App\Http\Controllers\AdminController;
@endphp

@section("content")

@if (!$isCustom) <span class="ghost">Produkt <strong>{{ $product?->name }}</strong> został zaimportowany od zewnętrznego dostawcy i części jego parametrów nie można edytować</span> @endif
<span class="ghost">Dodane tutaj opisy, zdjęcia i zakładki pojawią się w Ofertowniku przed informacjami podanymi w rodzinie produktu.</span>


<form action="{{ route('update-products') }}" method="post" class="flex-down" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="id" value="{{ $product?->id }}">
    <input type="hidden" name="_model" value="App\Models\Product">
    <input type="hidden" name="product_family_id" value={{ $copyFrom && class_basename($copyFrom::class) == 'Product' ? $copyFrom?->productFamily->id : $copyFrom?->id ?? $product?->productFamily->id }}>

    <x-magazyn-section title="Wariant produktu">
        <x-slot:buttons>
            @if ($product && $isCustom)
            <x-button
                label="Kopiuj na nowy wariant"
                :action="route('products-edit', ['copy_from' => $product->id])"
                target="_blank"
            />
            @endif
        </x-slot:buttons>

        <div class="grid" style="--col-count: 2">
            <x-input-field type="text" label="Nazwa" name="name" :value="$copyFrom->name ?? $product?->name" :disabled="!$isCustom" />
            @if ($product)
            <x-input-field type="text" label="SKU" name="_front_id" :value="$product?->front_id" disabled />
            @endif
        </div>

        <p class="ghost">
            W <strong>Ofertowniku</strong> treść wpisana w polu poniżej będzie poprzedzona tekstem <strong>{{ $product->productFamily->description_label ?? "Opis" }}:</strong>
        </p>
        <x-ckeditor
            label="Opis"
            name="description"
            :value="($copyFrom && class_basename($copyFrom::class) == 'Product' ? $copyFrom->description : null)
                ?? $product?->description"
            :disabled="!$isCustom"
        />
    </x-magazyn-section>

    <div class="grid" style="--col-count: 3">
        @if ($product)

        <x-magazyn-section title="Zdjęcia">
            <x-slot:buttons>
                @if ($isCustom)
                <x-button :action="route('files')" label="Wgraj nowe" target="_blank" />
                @endif
            </x-slot:buttons>

            <p class="ghost">
                Zdjęcia tego wariantu produktu.
                Pojawią się przed zdjęciami dla całej rodziny.
                Pierwsze zdjęcie z całej tej listy (zdjęcia wariantów + zdjęcia rodziny) będzie pojawiać się w kafelku na listingu produktów.
            </p>

            <div class="flex-right">
                @foreach ($product->images as $img)
                <img class="thumbnail" src="{{ url($img) }}" />
                @endforeach
            </div>

            <div class="flex-right">
                <x-input-field type="JSON"
                    name="image_urls" label="Zdjęcia"
                    :column-types="[
                        'Kolejność' => 'number',
                        'Ścieżka' => 'url',
                    ]"
                    :disabled="!$isCustom"
                    :value="$product->images"
                />
            </div>

            {{-- disabled editing manually
            <h3>Miniatury</h3>
            <p class="ghost">
                Pomniejszone zdjęcia, które wyświetlają się zamiast głównych zdjęć w miejscach takich jak galeria zdjęć, aby przyspieszyć ich ładowanie dla użytkownika.
                <strong>N-ta miniatura jest powiązana z n-tym ze zdjęć.</strong>
                Brak wgranych miniatur sprawia, że wyświetlane są zdjęcia w pełnej rozdzielczości.
            </p>

            <input type="hidden" name="thumbnails" value="{{ $product->thumbnails ? $product->thumbnails->join(",") : "" }}">
            <table class="thumbnails">
                <thead>
                    <tr>
                        <th>Zdjęcie</th>
                        <th>Nazwa</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                @if ($product->thumbnails)
                @foreach ($product->thumbnails->filter(fn($img) => $img) as $img)
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

        <x-magazyn-section title="Cechy">
            <p class="ghost">
                Warianty tego produktu są podzielone na:
                <strong>{{ $product?->productFamily->altAttribute?->name ?? "Kolory" }}</strong>
            </p>

            <div class="flex-right middle stretch">
                @if ($product?->productFamily->alt_attribute_id)
                <x-multi-input-field name="variant_name"
                    label="Przypisany wariant"
                    :value="$product?->variant_name"
                    :options="collect($product?->productFamily->altAttribute->variant_names)->combine($product?->productFamily->altAttribute->variant_names)"
                    empty-option="Wybierz..."
                    :disabled="!$isCustom"
                    onchange="changeVariantTile(event.target.value)"
                />
                <x-variant-tile :variant="$product?->attribute_for_tile" />
                @else
                <x-multi-input-field name="variant_name"
                    label="Przypisany kolor"
                    :value="$product?->color->name"
                    :options="$primaryColors"
                    empty-option="Wybierz..."
                    :disabled="!$isCustom"
                    onchange="changePrimaryColor(event.target.value)"
                />
                <x-variant-tile :color="$product?->color" />
                @endif
            </div>

            @if (!$isCustom)
            <x-input-field type="text" name="variant_name" label="Oryginalna nazwa koloru" :value="$product->variant_name" :disabled="!$isCustom" onchange="changePrimaryColor(event.target.value)" />
            @endif

            <script>
            const changePrimaryColor = (color_name) => {
                fetch(`/api/primary-colors/tile/${color_name}`)
                    .then(res => {
                        if (!res.ok) throw new Error(res.status)
                        return res.text()
                    })
                    .then(tile => {
                        document.querySelector(".variant-tile").replaceWith(fromHTML(tile))
                    })
                    .catch((e) => {
                        document.querySelector(".variant-tile").replaceWith(fromHTML(`<x-variant-tile :color="$product?->color" />`))
                    })
            }
            const changeVariantTile = (variant_name) => {
                fetch(`/api/attributes/alt/tile/{{ $product?->productFamily->alt_attribute_id }}/${variant_name}`)
                    .then(res => {
                        if (!res.ok) throw new Error(res.status)
                        return res.text()
                    })
                    .then(tile => {
                        document.querySelector(".variant-tile").replaceWith(fromHTML(tile))
                    })
                    .catch((e) => {
                        document.querySelector(".variant-tile").replaceWith(fromHTML(`<x-variant-tile :variant="$product?->attribute_for_tile" />`))
                    })
            }
            </script>

            <h3>Rozmiary</h3>

            <table class="sizes">
                <thead>
                    <tr>
                        <th>Rozmiar</th>
                        <th>Kod</th>
                        <th>Pełne SKU</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($product->sizes ?? [] as $size)
                    <tr>
                        <td>
                            <x-input-field name="sizes[size_names][]"
                                :value="$size['size_name']"
                                :disabled="!$isCustom"
                                required
                            />
                        </td>
                        <td>
                            <x-input-field name="sizes[size_codes][]"
                                :value="$size['size_code']"
                                :disabled="!$isCustom"
                                required
                            />
                        </td>
                        <td>
                            <x-input-field name="sizes[full_skus][]"
                                :value="$size['full_sku']"
                                :disabled="!$isCustom"
                                required
                            />
                        </td>
                        @if ($isCustom) <td class="clickable" onclick="deleteSize(this)">Usuń</td> @endif
                    </tr>
                    @endforeach
                </tbody>
                @if ($isCustom)
                <tfoot>
                    <tr>
                        <td class="clickable" onclick="addSize()">Dodaj</td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <script>
            const addSize = () => {
                let sizes = document.querySelector(".sizes tbody")
                sizes.insertAdjacentHTML("beforeend", `<tr>
                    <td><x-input-field name="sizes[size_names][]" required /></td>
                    <td><x-input-field name="sizes[size_codes][]" required /></td>
                    <td><x-input-field name="sizes[full_skus][]" required /></td>
                    @if ($isCustom) <td class="clickable" onclick="deleteSize(this)">Usuń</td> @endif
                </tr>`)
            }

            const deleteSize = (btn) => {
                btn.closest("tr").remove()
            }
            </script>

            <h3>Cechy dodatkowe</h3>

            <x-input-field type="JSON"
                name="extra_filtrables" label="Dodaj cechy dodatkowe, po których może być filtrowany ten produkt. Jeśli dana cecha ma posiadać więcej niż 1 wartość, oddziel je znakiem |."
                :column-types="[
                    'Nazwa' => 'text',
                    'Wartości' => 'text',
                ]"
                :disabled="!$isCustom"
                :value="array_map(fn($fs) => implode('|', $fs), $product->extra_filtrables ?? []) ?: null"
            />
        </x-magazyn-section>

        <x-magazyn-section title="Cena">
            <x-input-field type="number" name="price" label="Cena" :value="$product->price" min="0" step="0.01" :disabled="!$isCustom" />
            <x-input-field type="checkbox" name="enable_discount" label="Dozwolone zniżki (Kwazar)" :value="$product->enable_discount" :disabled="!$isCustom" />
        </x-magazyn-section>

        @endif
    </div>

    @if ($product)
    <x-magazyn-section title="Zakładki">
        <x-slot:buttons>
            @if ($isCustom)
                <x-button :action="route('products-import-specs', ['entity_name' => 'Product', 'id' => $product->id])" label="Importuj tabelę specyfikacji" />
                <span class="button" onclick="newTab()">Dodaj nową zakładkę</span>
            @endif
        </x-slot:buttons>

        <x-app.loader text="Przetwarzanie" />
        <x-product.tabs-editor :tabs="$product->tabs" :editable="$isCustom" />
        <script src="{{ asset("js/tabs-editor.js") }}" defer>
        toggleLoader()
        </script>
    </x-magazyn-section>
    @endif

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($product)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        <a class="button" href="{{ route('products-edit-family', ['id' => $product->productFamily->prefixed_id]) }}">Wróć</a>
        @endif
    </div>
</form>

@endsection
