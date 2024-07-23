@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@section("content")

<form action="{{ route('update-products') }}" method="post" enctype="multipart/form-data">
    @csrf

    <h2>Produkt</h2>

    <x-input-field type="text" label="SKU" name="id" :value="$product?->id" />
    <x-input-field type="text" label="Nazwa" name="name" :value="$product?->name" />
    <x-input-field type="TEXT" label="Opis" name="description" :value="$product?->description" />

    @if ($product)
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
                <td><img class="inline" src="{{ url($img) }}" /></td>
                <td>{{ basename($img) }}</td>
                <td><span class="clickable" onclick="deleteImage(this)">Usuń</span></td>
            </tr>
        @endforeach
        @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan=3><x-input-field type="file" label="Dodaj zdjęcia" name="newImages[]" multiple /></td>
            </tr>
        </tfoot>
    </table>

    <script>
    const deleteImage = (btn) => {
        let ids = document.querySelector("input[name=images]").value.split(",")
        ids = ids.filter(id => id != btn.closest("tr").getAttribute("attr-name"))
        document.querySelector("input[name=images]").value = ids.join(",")

        btn.closest("tr").remove()
    }
    </script>

    <h2>Cechy</h2>

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
    @endif

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
