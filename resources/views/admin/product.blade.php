@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@php
use App\Http\Controllers\AdminController;
@endphp

@section("content")

@if (!$isCustom) <span class="ghost">Produkt <strong>{{ $product?->name }}</strong> został zaimportowany od zewnętrznego dostawcy i części jego parametrów nie można edytować</span> @endif

<form action="{{ route('update-products') }}" method="post" enctype="multipart/form-data">
    @csrf

    @if (!$isCustom) <input type="hidden" name="id" value="{{ $product?->id }}"> @endif

<div class="flex-right separate-children">
<div>

    <h2>Produkt</h2>

    <x-input-field type="text" label="SKU" name="id" :value="$product?->id" onchange="validateCustomId(this)" :disabled="!$isCustom" />
    <x-input-field type="text" label="SKU rodziny" name="product_family_id" :value="$product?->product_family_id" :disabled="!$isCustom" />
    <x-input-field type="text" label="Nazwa" name="name" :value="$product?->name" :disabled="!$isCustom" />
    <x-ckeditor label="Opis" name="description" :value="$product?->description" :disabled="!$isCustom" />
    <x-input-field type="text" label="Kategoria dostawcy" name="original_category" :value="$product?->original_category" :disabled="!$isCustom" />
    <script>
    const validateCustomId = (input) => {
        if (input.value.substring(0, 3) != "{{ AdminController::CUSTOM_PRODUCT_PREFIX }}") {
            input.value = "{{ AdminController::CUSTOM_PRODUCT_PREFIX }}" + input.value
        }
        const productFamilyInput = document.querySelector(`input[name=product_family_id]`)
        if (!productFamilyInput.value) {
            productFamilyInput.value = input.value
        }
    }
    </script>
</div>

    @if ($product)

<div>
    <h2>Zdjęcia</h2>

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
                    <span class="clickable" onclick="deleteImage(this)">Usuń</span>
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
                    <span class="clickable" onclick="deleteImage(this)">Usuń</span>
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
</div>

<div>
    <h2>Cechy</h2>

    <div class="flex-right">
        <x-input-field type="text" name="original_color_name" label="Oryginalna nazwa koloru" :value="$product->original_color_name" :disabled="!$isCustom" />
        <x-color-tag :color="$product?->color" />
    </div>

    <script>
    const changeMainAttributeColor = (attr_id) => {
        fetch(`/api/main-attributes/${attr_id}`).then(res => res.json()).then(attr => {
            document.querySelector(".color-tile").style = `--tile-color: ${attr.color}`
        })
    }
    </script>

    <h3>Cechy dodatkowe</h3>

    <input type="hidden" name="attributes" value="{{ $product->attributes ? implode(",", $product->attributes->pluck("id")->all()) : "" }}">
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
                <td><span class="clickable" onclick="deleteVariant(this)">Usuń</span></td>
            </tr>
        @endforeach
        @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan=3>
                    <select>
                        <option value="" selected></option>
                    @foreach (\App\Models\Attribute::all() as $attr)
                        <option value="{{ $attr->id }}">{{ $attr->name }}</option>
                    @endforeach
                    </select>
                </td>
                <td><span class="clickable" onclick="addVariant(this)">Dodaj</span></td>
            </tr>
        </tfoot>
    </table>

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
                        <td><span class="clickable" onclick="deleteVariant(this)">Usuń</span></td>
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
</div>

<div>
    <h2>Cena</h2>
    <x-input-field type="number" name="price" label="Cena" :value="$product->price" min="0" step="0.01" :disabled="!$isCustom" />
</div>

<div>
    <h2>Zakładki</h2>
    <span class="ghost">🚧 edytor w budowie</span>

    <input type="hidden" name="tabs" value="{!! json_encode($product->tabs ?? []) !!}">
    <div class="tabs"></div>

    @if ($isCustom)
    <span class="clickable" onclick="newTab()">Dodaj nową zakładkę</span>

    <script defer>
    //! tab editor logic !//
    let tabs = {!! json_encode($product->tabs ?? []) !!}

    const buildTabs = () => {
        const tabsContainer = document.querySelector(".tabs")
        tabsContainer.innerHTML = ""

        const output = tabs.map((tab, i) => {
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
                            ${cell.content.map((value, label) => `<tr>
                                <td>${label}</td>
                                <td>${value}</td>
                                @if ($isCustom) <td>Usuń</td> @endif
                            </tr>`).join("")}
                        </tbody>
                    </table>`
                    break

                    case "text": cellContents = `<x-ckeditor label="Treść" name="tabs[${i}][cells][${j}][content]" value="${cell.content}" :disabled="!$isCustom" />`
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
                            ${cell.content.map((value, label) => `<tr>
                                <td>${label}</td>
                                <td>${value}</td>
                                @if ($isCustom) <td>Usuń</td> @endif
                            </tr>`).join("")}
                        </tbody>
                    </table>`
                    break
                }

                return `<div>
                    <x-input-field type="text" name="tabs[${i}][cells][${j}][heading]" label="Nagłówek" value="${cell.heading ?? ""}" :disabled="!$isCustom" />
                    <x-multi-input-field name="tabs[${i}][cells][${j}][type]" label="Typ komórki" value="${cell.type}" :options="['tabela' => 'table', 'tekst' => 'text', 'przyciski' => 'tiles']" :disabled="!$isCustom" />

                    ${(Array.isArray(cellContents) ? cellContents.join("") : cellContents) ?? ""}

                    <span class="clickable" onclick="deleteCell()">Usuń komórkę</span>
                </div>`
            })

            return `<div class="tab">
                <h3>Zakładka ${i + 1}</h3>
                <x-input-field type="text" name="tabs[${i}][name]" label="Nazwa" value="${tab.name}" :disabled="!$isCustom" />

                <h4>Komórki</h4>
                <div class="flex-down separate-children">${cells?.join("") ?? ""}</div>
                <span class="clickable" onclick="newCell(${i})">Dodaj nową komórke</span>

                <span class="clickable" onclick="deleteTab(${i})">Usuń zakładke</span>
            </div>`
        })

        console.log(tabs)
        output.forEach(tab => tabsContainer.append(fromHTML(tab)))
        document.querySelector(`input[name=tabs]`).value = JSON.stringify(tabs)
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

    // initialization
    buildTabs()
    </script>
    @endif
</div>

    @endif

</div>

    <div class="flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($product)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
    </div>
    <div class="flex-right center">
        <a href="{{ route('products') }}">Wróć</a>
    </div>
</form>

@endsection
