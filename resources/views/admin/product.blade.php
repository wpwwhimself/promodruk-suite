@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@php
use App\Http\Controllers\AdminController;
@endphp

@section("content")

@if (!$isCustom) <span class="ghost">Produkt <strong>{{ $product?->name }}</strong> został zaimportowany od zewnętrznego dostawcy i części jego parametrów nie można edytować</span> @endif

<form action="{{ route('update-products') }}" method="post" class="flex-down" enctype="multipart/form-data">
    @csrf

    @if (!$isCustom) <input type="hidden" name="id" value="{{ $product?->id }}"> @endif

    <x-magazyn-section title="Produkt">
        <x-slot:buttons>
            @if ($product && $isCustom)
            <x-button
                label="Kopiuj na nowy wariant"
                :action="route('products-edit', ['copy_from' => $product->id])"
                target="_blank"
            />
            @endif
        </x-slot:buttons>

        <x-input-field type="text" label="Producent" name="source" :value="$product?->source ?? 'produkt własny'" disabled />
        <x-input-field type="text" label="SKU" name="id" :value="$product?->id" onchange="validateCustomId(this)" :disabled="!$isCustom" />
        <x-input-field type="text" label="SKU rodziny" name="product_family_id" :value="$copyFrom->product_family_id ?? $product?->product_family_id" :disabled="!$isCustom" />
        <x-input-field type="text" label="Nazwa" name="name" :value="$copyFrom->name ?? $product?->name" :disabled="!$isCustom" />
        <x-ckeditor label="Opis" name="description" :value="$copyFrom->description ?? $product?->description" :disabled="!$isCustom" />
        <x-input-field type="text" label="Kategoria dostawcy" name="original_category" :value="$copyFrom->original_category ?? $product?->original_category" :disabled="!$isCustom" />
        <script>
        const validateCustomId = (input) => {
            if (input.value.substring(0, "{{ AdminController::CUSTOM_PRODUCT_PREFIX }}".length) != "{{ AdminController::CUSTOM_PRODUCT_PREFIX }}") {
                input.value = "{{ AdminController::CUSTOM_PRODUCT_PREFIX }}" + input.value
            }
            const productFamilyInput = document.querySelector(`input[name=product_family_id]`)
            if (!productFamilyInput.value) {
                productFamilyInput.value = input.value
            }
        }
        </script>
    </x-magazyn-section>

    <div class="grid" style="--col-count: 3">
        @if ($product)

        <x-magazyn-section title="Zdjęcia">
            <input type="hidden" name="images" value="{{ $product->images ? $product->images->join(",") : "" }}">
            <table class="images">
                <thead>
                    <tr>
                        <th>Zdjęcie</th>
                        <th>Nazwa</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                @if ($product->images)
                @foreach ($product->images as $img)
                    <tr attr-name="{{ $img }}">
                        <td><img class="inline" src="{{ url($img) }}" {{ Popper::pop("<img class='thumbnail' src='".url($img)."' />") }} /></td>
                        <td>{{ basename($img) }}</td>
                        <td>
                            @if (Str::startsWith($img, env("APP_URL")) && $isCustom)
                            <span class="button" onclick="deleteImage(this)">Usuń</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                @if ($isCustom)
                <tfoot>
                    <tr>
                        <td colspan=3><x-input-field type="file" label="Dodaj zdjęcia" name="newImages[]" multiple /></td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <script>
            const deleteImage = (btn) => {
                let ids = document.querySelector("input[name=images]").value.split(",")
                ids = ids.filter(id => id != btn.closest("tr").getAttribute("attr-name"))
                document.querySelector("input[name=images]").value = ids.join(",")

                btn.closest("tr").remove()
            }
            </script>

            <h3>Miniatury</h3>

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
                            <span class="button" onclick="deleteImage(this)">Usuń</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                @if ($isCustom)
                <tfoot>
                    <tr>
                        <td colspan=3><x-input-field type="file" label="Dodaj zdjęcia" name="newThumbnails[]" multiple /></td>
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
            }
            </script>
        </x-magazyn-section>

        <x-magazyn-section title="Cechy">
            <div class="flex-right middle">
                <x-input-field type="text" name="original_color_name" label="Oryginalna nazwa koloru" :value="$product->original_color_name" :disabled="!$isCustom" onchange="changeMainAttributeColor(event.target.value)" />
                <x-color-tag :color="$product?->color" />
            </div>

            <script>
            const changeMainAttributeColor = (color_name) => {
                fetch(`/api/main-attributes/tile/${color_name}`)
                    .then(res => {
                        if (!res.ok) throw new Error(res.status)
                        return res.text()
                    })
                    .then(tile => {
                        document.querySelector(".color-tile").replaceWith(fromHTML(tile))
                    })
                    .catch((e) => {
                        document.querySelector(".color-tile").replaceWith(fromHTML(`<x-color-tag :color="$product?->color" />`))
                    })
            }
            </script>

            <h3>Cechy dodatkowe</h3>

            <input type="hidden" name="attributes" value="{{ $product->attributes ? implode(",", $product->attributes->pluck("id")->all()) : "" }}">

            @if ($attributes->isEmpty())
            <p class="ghost">Brak utworzonych cech dodatkowych. Dodaj je w menu <b>Cechy</b>.</p>
            @else
            <table class="variants">
                <thead>
                    <tr>
                        <th>Nazwa</th>
                        <th>Typ</th>
                        <th>L. war.</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                @if ($product->attributes)
                @foreach ($product->attributes as $attr)
                    <tr attr-id="{{ $attr->id }}">
                        <td>{{ $attr->name }}</td>
                        <td>{{ $attr->type }}</td>
                        <td>{{ $attr->variants->count() }}</td>
                        <td><span class="button" onclick="deleteVariant(this)">Usuń</span></td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan=3>
                            <x-multi-input-field
                                :options="$attributes"
                                label=""
                                name="_attr"
                                empty-option="Wybierz..."
                            />
                        </td>
                        <td><span class="button" onclick="addVariant(this)">Dodaj</span></td>
                    </tr>
                </tfoot>
            </table>
            @endif

            <script>
            const addVariant = (btn) => {
                const new_attr_id = btn.closest("tr").querySelector("select").value

                // clear adder
                btn.closest("tr").querySelector("select").value = "";

                if (document.querySelector("input[name=attributes]").value.split(",").includes(new_attr_id)) return

                // gather new variant data
                fetch(`/api/attributes/${new_attr_id}`)
                    .then(res => res.json())
                    .then(attr => {
                        document.querySelector(".variants tbody")
                            .append(fromHTML(`<tr attr-id="${attr.id}">
                                <td>${attr.name}</td>
                                <td>${attr.type}</td>
                                <td>${attr.variants.length}</td>
                                <td><span class="button" onclick="deleteVariant(this)">Usuń</span></td>
                            </tr>`))

                        let ids = document.querySelector("input[name=attributes]").value.split(",")
                        ids.push(attr.id)
                        document.querySelector("input[name=attributes]").value = ids.join(",")
                    })
            }
            const deleteVariant = (btn) => {
                let ids = document.querySelector("input[name=attributes]").value.split(",")
                ids = ids.filter(id => id != btn.closest("tr").getAttribute("attr-id"))
                document.querySelector("input[name=attributes]").value = ids.join(",")

                btn.closest("tr").remove()
            }
            </script>
        </x-magazyn-section>

        <x-magazyn-section title="Cena">
            <x-input-field type="number" name="price" label="Cena" :value="$product->price" min="0" step="0.01" :disabled="!$isCustom" />
        </x-magazyn-section>
    </div>

    <x-magazyn-section title="Zakładki">
        <x-slot:buttons>
            @if ($isCustom) <span class="button" onclick="newTab()">Dodaj nową zakładkę</span> @endif
        </x-slot:buttons>

        <p class="ghost">Uwaga: najpierw dodaj szkielet zakładek (zakładki, komórki), a potem ich treść - inaczej stracisz informacje!</p>

        <input type="hidden" name="tabs">
        <div class="tabs"></div>

        <script defer>
        //! tab editor logic !//
        let tabs = {!! json_encode($product->tabs) !!} ?? []

        const buildTabs = () => {
            const tabsContainer = document.querySelector(".tabs")
            tabsContainer.innerHTML = ""

            const output = tabs?.map((tab, i) => {
                const cells = tab.cells?.map((cell, j) => {
                    let cellContents = "";
                    switch (cell.type) {
                        case "table": cellContents = `<table>
                            <thead>
                                <tr>
                                    <th>Etykieta</th>
                                    <th>Wartość</th>
                                    @if ($isCustom) <th>Akcja</th> @endif
                                </tr>
                            </thead>
                            <tbody>
                                ${objectMap(cell.content, (value, label) => `<tr>
                                    <td><input name="tabs[${i}][cells][${j}][content][labels][]" value="${label}" :disabled="!$isCustom" onchange="updateTableRows(${i}, ${j})" {{ !$isCustom ? 'disabled' : '' }} /></td>
                                    <td><input name="tabs[${i}][cells][${j}][content][values][]" value="${value}" :disabled="!$isCustom" onchange="updateTableRows(${i}, ${j})" {{ !$isCustom ? 'disabled' : '' }} /></td>
                                    @if ($isCustom) <td class="clickable" onclick="deleteTableRow(this, ${i}, ${j})">Usuń</td> @endif
                                </tr>`).join("") ?? ""}
                            </tbody>
                            @if ($isCustom)
                            <tfoot>
                                <tr>
                                    <td class="clickable" onclick="addTableRow(${i}, ${j})">Dodaj wiersz</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>`
                        break

                        case "text": cellContents = `<textarea name="tabs[${i}][cells][${j}][content]" :disabled="!$isCustom">${cell.content}</textarea><br>`
                        break

                        case "tiles": cellContents = `<table>
                            <thead>
                                <tr>
                                    <th>Etykieta</th>
                                    <th>URL</th>
                                    @if ($isCustom) <th>Akcja</th> @endif
                                </tr>
                            </thead>
                            <tbody>
                                ${objectMap(cell.content, (value, label) => `<tr>
                                    <td><input name="tabs[${i}][cells][${j}][content][labels][]" value="${label}" :disabled="!$isCustom" onchange="updateTableRows(${i}, ${j})" {{ !$isCustom ? 'disabled' : '' }} /></td>
                                    <td><input name="tabs[${i}][cells][${j}][content][values][]" value="${value}" :disabled="!$isCustom" onchange="updateTableRows(${i}, ${j})" {{ !$isCustom ? 'disabled' : '' }} /></td>
                                    @if ($isCustom) <td class="clickable" onclick="deleteTableRow(this, ${i}, ${j})">Usuń</td> @endif
                                </tr>`).join("") ?? ""}
                            </tbody>
                            @if ($isCustom)
                            <tfoot>
                                <tr>
                                    <td class="clickable" onclick="addTableRow(${i}, ${j})">Dodaj wiersz</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>`
                        break
                    }

                    // set empty heading if not defined
                    cell.heading ||= ""
                    console.log(cellContents)

                    return `<x-magazyn-section title="Komórka ${j + 1}">
                        <x-slot:buttons>
                            @if ($isCustom)
                            <span class="button" onclick="deleteCell(${i}, ${j})">Usuń komórkę</span>
                            @endif
                        </x-slot:buttons>

                        <x-input-field type="text" name="tabs[${i}][cells][${j}][heading]"
                            label="Nagłówek"
                            value="${cell.heading}"
                            :disabled="!$isCustom"
                            onchange="changeCellHeading(${i}, ${j}, event.target.value)"
                        />
                        <x-multi-input-field name="tabs[${i}][cells][${j}][type]"
                            label="Typ komórki"
                            value="${cell.type}"
                            :options="['tabela' => 'table', 'tekst' => 'text', 'przyciski' => 'tiles']"
                            :disabled="!$isCustom"
                            onchange="changeCellType(${i}, ${j}, event.target.value)"
                        />

                        ${cellContents ?? ""}
                    </x-magazyn-section>`
                })

                return `<x-magazyn-section title="Zakładka ${i + 1}" class="tab">
                    <x-slot:buttons>
                        @if ($isCustom)
                        <span class="button" onclick="newCell(${i})">Dodaj nową komórkę</span>

                        <div class="flex-right">
                            <span class="button" onclick="deleteTab(${i})">Usuń zakładkę</span>
                        </div>
                        @endif
                    </x-slot:buttons>

                    <x-input-field type="text" name="tabs[${i}][name]" label="Nazwa" value="${tab.name}" :disabled="!$isCustom" />

                    <div class="flex-down separate-children">${cells?.join("") ?? ""}</div>
                </x-magazyn-section>`
            })

            output.forEach(tab => tabsContainer.append(fromHTML(tab)))
            document.querySelector(`input[name=tabs]`).value = JSON.stringify(tabs)

            // set proper types of cells
            tabs?.forEach((tab, i) => {
                tab.cells?.forEach((cell, j) => {
                    document.querySelector(`select[name="tabs[${i}][cells][${j}][type]"]`).value = cell.type
                })
            })
        }

        const newTab = () => {
            tabs.push({
                name: "",
                cells: []
            })
            buildTabs()
        }
        const deleteTab = (index) => {
            tabs = tabs.filter((tab, i) => i != index)
            buildTabs()
        }

        const newCell = (tab_index) => {
            tabs[tab_index].cells = [...tabs[tab_index].cells ?? [], {
                type: "text",
                content: "",
            }]
            buildTabs()
        }
        const changeCellHeading = (tab_index, cell_index, new_value) => {
            tabs[tab_index].cells[cell_index]["heading"] = new_value
            buildTabs()
        }
        const changeCellType = (tab_index, cell_index, new_type) => {
            tabs[tab_index].cells[cell_index] = {
                type: new_type,
                content: (new_type == "text") ? "" : [],
            }
            buildTabs()
        }
        const deleteCell = (tab_index, cell_index) => {
            tabs[tab_index].cells = tabs[tab_index].cells.filter((cell, i) => i != cell_index)
            buildTabs()
        }

        const addTableRow = (tab_index, cell_index) => {
            tabs[tab_index].cells[cell_index].content = {
                ...tabs[tab_index].cells[cell_index].content ?? [],
                nowy: "",
            }
            buildTabs()
        }
        const updateTableRows = (tab_index, cell_index) => {
            const labels = Array.from(document.querySelectorAll(`input[name^="tabs[${tab_index}][cells][${cell_index}][content][labels]"]`)).map(field => field.value)
            const values = Array.from(document.querySelectorAll(`input[name^="tabs[${tab_index}][cells][${cell_index}][content][values]"]`)).map(field => field.value)

            tabs[tab_index].cells[cell_index].content = {}
            labels.forEach((label, i) => {
                tabs[tab_index].cells[cell_index].content[label] = values[i]
            })
            buildTabs()
        }
        const deleteTableRow = (btn, tab_index, cell_index) => {
            btn.closest("tr").remove()
            updateTableRows(tab_index, cell_index)
        }

        // initialization
        buildTabs()
        </script>
    </x-magazyn-section>

    @endif

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($product)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
        <a class="button" href="{{ route('products') }}">Wróć</a>
    </div>
</form>

@endsection
