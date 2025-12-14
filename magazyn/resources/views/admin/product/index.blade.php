@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@php
use App\Http\Controllers\AdminController;
@endphp

@section("content")

@if (!$isCustom) <span class="ghost">Produkt <strong>{{ $product?->name }}</strong> został zaimportowany od zewnętrznego dostawcy i części jego parametrów nie można edytować</span> @endif
<span class="ghost">Dodane tutaj opisy, zdjęcia i zakładki pojawią się w Ofertowniku przed informacjami podanymi w rodzinie produktu.</span>


<x-shipyard.app.form :action="route('update-products')" method="post" class="flex down" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{{ $product?->id }}">
    <input type="hidden" name="_model" value="App\Models\Product">
    <input type="hidden" name="product_family_id" value={{ $copyFrom && class_basename($copyFrom::class) == 'Product' ? $copyFrom?->productFamily->id : $copyFrom?->id ?? $product?->productFamily->id }}>

    <x-magazyn-section title="Wariant produktu" :icon="model_icon('products')">
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
            <x-input-field type="text" label="Nazwa" name="name" :value="$copyFrom->name ?? $product?->name" :disabled="!$isCustom" required />
            @if ($product)
            <x-input-field type="text" label="SKU" name="_front_id" :value="$product?->front_id" disabled />
            @endif
        </div>

        <p class="ghost">
            W <strong>Ofertowniku</strong> treść wpisana w polu poniżej będzie poprzedzona tekstem <strong>{{ $product->productFamily->description_label ?? "Opis" }}:</strong>
        </p>
        <x-shipyard.ui.input type="HTML"
            label="Opis"
            name="description"
            :value="($copyFrom && class_basename($copyFrom::class) == 'Product' ? $copyFrom->description : null)
                ?? $product?->description"
            :disabled="!$isCustom"
        />
        @if ($product?->specification)
        <label for="">Specyfikacja</label>
        <p class="ghost">Edycja specyfikacji jest obecnie nieobsługiwana.</p>
        <ul role="specification">
            @foreach ($product->specification as $key => $value)
            <li>
                <b>{{ $key }}:</b>
                @if (!is_array($value))
                {{ $value }}
                @else
                <ul>
                    @foreach ($value as $vvalue)
                    <li>{{ $vvalue }}</li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
        </ul>
        @endif
    </x-magazyn-section>

    <div class="grid" style="--col-count: 3">
        @if ($product)

        <x-magazyn-section title="Zdjęcia" icon="image">
            <x-slot:buttons>
                @if ($isCustom)
                <x-button :action="route('files')" label="Wgraj nowe" target="_blank" />
                @endif
            </x-slot:buttons>

            <p class="ghost">
                Zdjęcia tego wariantu produktu.
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

            <div class="flex right">
                @foreach ($product->images as $img)
                <img class="thumbnail" src="{{ url($img) }}" />
                @endforeach
            </div>

            <x-input-field type="JSON"
                name="image_urls" label="Zdjęcia"
                :column-types="[
                    'Kolejność' => 'number',
                    'Ścieżka' => 'url',
                ]"
                :disabled="!$isCustom"
                :value="$product->images"
            />

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

        <x-magazyn-section title="Cechy" :icon="model_icon('main-attributes')">
            <p class="ghost">
                Warianty tego produktu są podzielone na:
                <strong>{{ $product?->productFamily->alt_attributes["name"] ?? "Kolory" }}</strong>
            </p>

            <div class="flex down">
                <div class="flex right middle stretch">
                    @if ($product?->productFamily->alt_attributes)
                    <x-multi-input-field name="variant_name"
                        label="Przypisany wariant"
                        :value="$product?->variant_name"
                        :options="collect($product?->productFamily->alt_attribute_variants)->combine($product?->productFamily->alt_attribute_variants)"
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
                    fetch(`/api/attributes/alt/tile/{{ $product?->productFamily->id }}/${variant_name}`)
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

                <x-shipyard.app.h lvl="3" icon="arrow-expand">Rozmiary</x-shipyard.app.h>

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

                <x-shipyard.app.h lvl="3" :icon="model_icon('alt-attributes')">Cechy dodatkowe</x-shipyard.app.h>

                <p>Dodaj cechy dodatkowe, po których może być filtrowany ten produkt. Jeśli dana cecha ma posiadać więcej niż 1 wartość, oddziel je znakiem |.</p>

                <x-input-field type="JSON"
                    name="extra_filtrables" label="Cechy"
                    :column-types="[
                        'Nazwa' => 'text',
                        'Wartości' => 'text',
                    ]"
                    :disabled="!$isCustom"
                    :value="array_map(fn($fs) => implode('|', $fs), $product->extra_filtrables ?? []) ?: null"
                />
            </div>
        </x-magazyn-section>

        <x-magazyn-section title="Cena" icon="cash">
            <div class="flex down">
                <x-input-field type="number" name="price" label="Cena" :value="$product->price" min="0" step="0.01" :disabled="!$isCustom" />
                <x-shipyard.ui.input type="checkbox"
                    name="show_price"
                    label="Cena widoczna (Ofertownik)"
                    :checked="$product->show_price"
                    :disabled="!$isCustom"
                />
                <x-shipyard.ui.input type="number"
                    name="ofertownik_price_multiplier"
                    label="Mnożnik ceny (Ofertownik)"
                    :value="$product->ofertownik_price_multiplier"
                    min="0" step="0.01"
                />
                <x-shipyard.ui.input type="dummy-number"
                    name="ofertownik_price"
                    label="Cena widoczna w Ofertowniku"
                    hint="Wartość oparta na cenie pomnożonej przez mnożnik."
                    :value="round($product->price * ($product->ofertownik_price_multiplier ?? 1), 2)"
                />
                <x-shipyard.ui.input type="checkbox"
                    name="enable_discount"
                    label="Dozwolone zniżki (Kwazar)"
                    :checked="$product->enable_discount"
                />
            </div>
        </x-magazyn-section>

        @endif
    </div>

    @if ($product)
    <x-magazyn-section title="Zakładki" icon="tab">
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

    @unless ($isCustom)
    <x-magazyn-section title="Pozostałe informacje" icon="information">
        <div class="grid" style="--col-count: 2;">
            <div>
                <h3>Stan magazynowy</h3>
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Obecny st. mag.</th>
                            <th>Przewid. dost.</th>
                            <th>Termin p. dost.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->all_stocks ?? [] as $size)
                        <tr>
                            <th>{{ $size['id'] }}</th>
                            <td>{{ $size['current_stock'] }}</td>
                            <td>{{ $size['future_delivery_amount'] }}</td>
                            <td>{{ $size['future_delivery_date'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <h3>Znakowania</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Miejsce/techn./rozm.</th>
                            <th>Obrazki</th>
                            <th>Mod. ceny</th>
                            <th>Ceny</th>
                            <th>Koszt przyg.</th>
                            <th>Zniżka możliwa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->markings ?? [] as $marking)
                        <tr>
                            <td>
                                {{ $marking->position }}<br>
                                {{ $marking->technique }}<br>
                                {{ $marking->print_size }}
                            </td>
                            <td>
                                @foreach ($marking->images ?? [] as $img)
                                <img class="inline" src="{{ url($img) }}" {{ Popper::pop("<img class='thumbnail' src='".url($img)."' />") }} />
                                @endforeach
                            </td>
                            <td>{{ $marking->price_mod }}</td>
                            <td>
                                <ul class="scrollable" style="max-height: 4em;">
                                    @foreach ($marking->quantity_prices ?? [] as $lvl => $qpdata)
                                    <li><b>{{ $lvl }}</b>: {{ print_r($qpdata, true) }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>{{ as_number($marking->setup_price) ?? "–" }} zł</td>
                            <td>@if ($marking->enable_discount) ✅ @endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-magazyn-section>
    @endunless
    @endif

    <x-slot:actions>
        <x-shipyard.ui.button action="submit" name="mode" value="save" label="Zapisz" icon="check" class="primary" />
        @if ($product)
        <x-shipyard.ui.button action="submit" name="mode" value="delete" class="danger" label="Usuń" icon="delete" />
        <x-shipyard.ui.button :action="route('products-edit-family', ['id' => $product->productFamily->prefixed_id])" label="Wróć" icon="arrow-left" />
        @endif
    </x-slot:actions>
</x-shipyard.app.form>

@endsection
