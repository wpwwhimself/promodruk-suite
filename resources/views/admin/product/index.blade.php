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

        <div class="grid" style="--col-count: 3">
            <x-input-field type="text" label="Pochodzenie" name="source" :value="$product?->source ?? 'produkt własny'" disabled />
            <div class="flex-right stretch middle">
                <x-input-field type="dummy"
                    label="SKU rodziny"
                    name="product_family_id"
                    :value="$copyFrom?->{
                        class_basename($copyFrom::class) == 'ProductFamily'
                            ? 'id'
                            : 'product_family_id'
                    }
                        ?? $product?->product_family_id"
                    :disabled="!$isCustom"
                />
                <x-button label="»" :action="route('products-edit-family', ['id' => $copyFrom->product_family_id ?? $product?->product_family_id])" />
            </div>
            <x-input-field type="text" label="Sufiks wariantu" name="id_suffix" :value="$product?->id_suffix" :disabled="!$isCustom" />
        </div>
        <x-input-field type="text" label="Nazwa" name="name" :value="$copyFrom->name ?? $product?->name" :disabled="!$isCustom" />
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
        </x-magazyn-section>

        <x-magazyn-section title="Cechy">
            <div class="flex-right middle stretch">
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
                        <td><span class="clickable" onclick="deleteVariant(this)">Usuń</span></td>
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

        @endif
    </div>

    @if ($product)
    <x-magazyn-section title="Zakładki">
        <x-slot:buttons>
            @if ($isCustom) <span class="button" onclick="newTab()">Dodaj nową zakładkę</span> @endif
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
        @endif
        <a class="button" href="{{ route('products') }}">Wróć</a>
    </div>
</form>

@endsection
